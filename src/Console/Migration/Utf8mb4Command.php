<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Migration;

use DBConnection;
use Glpi\Console\AbstractCommand;
use Glpi\System\Requirement\DbConfiguration;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Utf8mb4Command extends AbstractCommand
{
   /**
    * Error code returned if migration failed on, at least, one table.
    *
    * @var integer
    */
    const ERROR_MIGRATION_FAILED_FOR_SOME_TABLES = 1;

   /**
    * Error code returned if DB configuration file cannot be updated.
    *
    * @var integer
    */
    const ERROR_UNABLE_TO_UPDATE_CONFIG = 2;

   /**
    * Error code returned if some tables are still using MyISAM engine.
    *
    * @var integer
    */
    const ERROR_INNODB_REQUIRED = 3;

   /**
    * Error code returned if some tables are still using Redundant/Compact row format.
    *
    * @var integer
    */
    const ERROR_DYNAMIC_ROW_FORMAT_REQUIRED = 4;

   /**
    * Error code returned if DB configuration is not compatible with large indexes.
    *
    * @var integer
    */
    const ERROR_INCOMPATIBLE_DB_CONFIG = 5;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:utf8mb4');
        $this->setDescription(__('Convert database character set from "utf8" to "utf8mb4".'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkForPrerequisites();
        $this->migrateToUtf8mb4();

        return 0; // Success
    }

   /**
    * Check for migration prerequisites.
    *
    * @return void
    */
    private function checkForPrerequisites(): void
    {
       // Check that DB configuration is compatible
        $config_requirement = new DbConfiguration($this->db);
        if (!$config_requirement->isValidated()) {
            $msg = '<error>' . __('Database configuration is not compatible with "utf8mb4" usage.') . '</error>';
            foreach ($config_requirement->getValidationMessages() as $validation_message) {
                $msg .= "\n" . '<error> - ' . $validation_message . '</error>';
            }
            throw new \Glpi\Console\Exception\EarlyExitException($msg, self::ERROR_INCOMPATIBLE_DB_CONFIG);
        }

       // Check that all tables are using InnoDB engine
        if (($myisam_count = $this->db->getMyIsamTables()->count()) > 0) {
            $msg = sprintf(__('%d tables are using the deprecated MyISAM storage engine.'), $myisam_count)
            . ' '
            . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:myisam_to_innodb');
            throw new \Glpi\Console\Exception\EarlyExitException('<error>' . $msg . '</error>', self::ERROR_INNODB_REQUIRED);
        }

       // Check that all tables are using the "Dynamic" row format
        if ($this->db->listTables('glpi\_%', ['row_format' => ['COMPACT', 'REDUNDANT']])->count() > 0) {
            $msg = sprintf(__('%d tables are still using Compact or Redundant row format.'), $myisam_count)
            . ' '
            . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:dynamic_row_format');
            throw new \Glpi\Console\Exception\EarlyExitException('<error>' . $msg . '</error>', self::ERROR_DYNAMIC_ROW_FORMAT_REQUIRED);
        }
    }

   /**
    * Migrate tables to utf8mb4.
    *
    * @return void
    */
    private function migrateToUtf8mb4(): void
    {

        $tables = [];

       // Find collations to update at table level
        $table_iterator = $this->db->getNonUtf8mb4Tables();
        foreach ($table_iterator as $table_data) {
            $tables[] = $table_data['TABLE_NAME'];
        }

        $errors = 0;

        if (count($tables) === 0) {
            $this->output->writeln('<info>' . __('No migration needed.') . '</info>');
        } else {
            sort($tables);

            $this->output->writeln(
                sprintf(
                    '<info>' . __('Found %s table(s) requiring migration to "utf8mb4".') . '</info>',
                    count($tables)
                )
            );

            $this->askForConfirmation();

           // Early update property to prevent warnings related to bad collation detection.
            $this->db->use_utf8mb4 = true;

            $progress_bar = new ProgressBar($this->output);
            $errors = 0;

            foreach ($progress_bar->iterate($tables) as $table) {
                $this->writelnOutputWithProgressBar(
                    sprintf(__('Migrating table "%s"...'), $table),
                    $progress_bar,
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );

                $result = $this->db->query(
                    sprintf('ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $table)
                );

                if (!$result) {
                     $this->writelnOutputWithProgressBar(
                         sprintf(__('<error>Error migrating table "%s".</error>'), $table),
                         $progress_bar,
                         OutputInterface::VERBOSITY_QUIET
                     );
                       $errors++;
                }
            }

            $this->output->write(PHP_EOL);
        }

        if (!DBConnection::updateConfigProperty(DBConnection::PROPERTY_USE_UTF8MB4, true)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Unable to update DB configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_UPDATE_CONFIG
            );
        }

        if ($errors) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Errors occured during migration.') . '</error>',
                self::ERROR_MIGRATION_FAILED_FOR_SOME_TABLES
            );
        }

        if (count($tables) > 0) {
            $this->output->writeln('<info>' . __('Migration done.') . '</info>');
        }
    }
}
