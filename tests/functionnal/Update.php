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

namespace tests\units;

/* Test for inc/update.class.php */

class Update extends \GLPITestCase
{
    public function testCurrents()
    {
        global $DB;
        $update = new \Update($DB);

        $expected = [
            'dbversion' => GLPI_SCHEMA_VERSION,
            'language'  => 'en_GB',
            'version'   => GLPI_VERSION
        ];
        $this->array($update->getCurrents())->isEqualTo($expected);
    }

    public function testInitSession()
    {
        global $DB;

        $update = new \Update($DB);
        session_destroy();
        $this->variable(session_status())->isIdenticalTo(PHP_SESSION_NONE);

        $update->initSession();
        $this->variable(session_status())->isIdenticalTo(PHP_SESSION_ACTIVE);

        $this->array($_SESSION)->hasKeys([
            'glpilanguage',
            'glpi_currenttime',
            'glpi_use_mode'
        ])->notHasKeys([
            'debug_sql',
            'debug_vars',
            'use_log_in_files'
        ]);
        $this->variable($_SESSION['glpi_use_mode'])->isIdenticalTo(\Session::DEBUG_MODE);
        $this->variable(error_reporting())->isIdenticalTo(E_ALL | E_STRICT);
    }

    public function testSetMigration()
    {
        global $DB;
        $update = new \Update($DB);
        $migration = null;
        $this->output(
            function () use (&$migration) {
                $migration = new \Migration(GLPI_VERSION);
            }
        )->isEmpty();

        $this->object($update->setMigration($migration))->isInstanceOf('Update');
    }


    public function migrationsProvider()
    {
        $path = realpath(GLPI_ROOT . '/install/migrations');

        $migrations_910_to_921 = [
            [
                'file'           => $path . '/update_9.1.0_to_9.1.1.php',
                'function'       => 'update910to911',
                'target_version' => '9.1.1',
            ],
            [
                'file'           => $path . '/update_9.1.1_to_9.1.3.php',
                'function'       => 'update911to913',
                'target_version' => '9.1.3',
            ],
            [
                'file'           => $path . '/update_9.1.x_to_9.2.0.php',
                'function'       => 'update91xto920',
                'target_version' => '9.2.0',
            ],
            [
                'file'           => $path . '/update_9.2.0_to_9.2.1.php',
                'function'       => 'update920to921',
                'target_version' => '9.2.1',
            ],
        ];

        $migrations_921_to_941 = [
            [
                'file'           => $path . '/update_9.2.1_to_9.2.2.php',
                'function'       => 'update921to922',
                'target_version' => '9.2.2',
            ],
            [
                'file'           => $path . '/update_9.2.2_to_9.2.3.php',
                'function'       => 'update922to923',
                'target_version' => '9.2.3',
            ],
            [
                'file'           => $path . '/update_9.2.x_to_9.3.0.php',
                'function'       => 'update92xto930',
                'target_version' => '9.3.0',
            ],
            [
                'file'           => $path . '/update_9.3.0_to_9.3.1.php',
                'function'       => 'update930to931',
                'target_version' => '9.3.1',
            ],
            [
                'file'           => $path . '/update_9.3.1_to_9.3.2.php',
                'function'       => 'update931to932',
                'target_version' => '9.3.2',
            ],
            [
                'file'           => $path . '/update_9.3.x_to_9.4.0.php',
                'function'       => 'update93xto940',
                'target_version' => '9.4.0',
            ],
            [
                'file'           => $path . '/update_9.4.0_to_9.4.1.php',
                'function'       => 'update940to941',
                'target_version' => '9.4.1',
            ],
        ];

        $migrations_941_to_957 = [
            [
                'file'           => $path . '/update_9.4.1_to_9.4.2.php',
                'function'       => 'update941to942',
                'target_version' => '9.4.2',
            ],
            [
                'file'           => $path . '/update_9.4.2_to_9.4.3.php',
                'function'       => 'update942to943',
                'target_version' => '9.4.3',
            ],
            [
                'file'           => $path . '/update_9.4.3_to_9.4.5.php',
                'function'       => 'update943to945',
                'target_version' => '9.4.5',
            ],
            [
                'file'           => $path . '/update_9.4.5_to_9.4.6.php',
                'function'       => 'update945to946',
                'target_version' => '9.4.6',
            ],
            [
                'file'           => $path . '/update_9.4.6_to_9.4.7.php',
                'function'       => 'update946to947',
                'target_version' => '9.4.7',
            ],
            [
                'file'           => $path . '/update_9.4.x_to_9.5.0.php',
                'function'       => 'update94xto950',
                'target_version' => '9.5.0',
            ],
            [
                'file'           => $path . '/update_9.5.1_to_9.5.2.php',
                'function'       => 'update951to952',
                'target_version' => '9.5.2',
            ],
            [
                'file'           => $path . '/update_9.5.2_to_9.5.3.php',
                'function'       => 'update952to953',
                'target_version' => '9.5.3',
            ],
            [
                'file'           => $path . '/update_9.5.3_to_9.5.4.php',
                'function'       => 'update953to954',
                'target_version' => '9.5.4',
            ],
            [
                'file'           => $path . '/update_9.5.4_to_9.5.5.php',
                'function'       => 'update954to955',
                'target_version' => '9.5.5',
            ],
            [
                'file'           => $path . '/update_9.5.5_to_9.5.6.php',
                'function'       => 'update955to956',
                'target_version' => '9.5.6',
            ],
            [
                'file'           => $path . '/update_9.5.6_to_9.5.7.php',
                'function'       => 'update956to957',
                'target_version' => '9.5.7',
            ],
        ];

        $migrations_957_to_1000 = [
            [
                'file'           => $path . '/update_9.5.x_to_10.0.0.php',
                'function'       => 'update95xto1000',
                'target_version' => '10.0.0',
            ],
        ];

        return [
            [
                // Validates version normalization (9.1 -> 9.1.0).
                'current_version'     => '9.1',
                'force_latest'        => false,
                'expected_migrations' => array_merge(
                    $migrations_910_to_921,
                    $migrations_921_to_941,
                    $migrations_941_to_957,
                    $migrations_957_to_1000
                ),
            ],
            [
                // Validate version normalization (9.4.1.1 -> 9.4.1).
                'current_version'     => '9.4.1.1',
                'force_latest'        => false,
                'expected_migrations' => array_merge(
                    $migrations_941_to_957,
                    $migrations_957_to_1000
                ),
            ],
            [
                // Validate 9.2.2 specific case.
                'current_version'     => '9.2.2',
                'force_latest'        => false,
                'expected_migrations' => array_merge(
                    $migrations_921_to_941,
                    $migrations_941_to_957,
                    $migrations_957_to_1000
                ),
            ],
            [
                // Dev versions always triggger latest migration
                'current_version'     => '10.0.0-dev',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // Alpha versions always triggger latest migration
                'current_version'     => '10.0.0-alpha',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // AlphaX versions always triggger latest migration
                'current_version'     => '10.0.0-alpha3',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // Beta versions always triggger latest migration
                'current_version'     => '10.0.0-beta',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // BetaX versions always triggger latest migration
                'current_version'     => '10.0.0-beta1',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // RC versions always triggger latest migration
                'current_version'     => '10.0.0-rc',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // RCX versions always triggger latest migration
                'current_version'     => '10.0.0-rc2',
                'force_latest'        => false,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // Force latests does not duplicate latest in list
                'current_version'     => '10.0.0-dev',
                'force_latest'        => true,
                'expected_migrations' => $migrations_957_to_1000,
            ],
            [
                // Validate that list is empty when version matches
                'current_version'     => '10.0.0',
                'force_latest'        => false,
                'expected_migrations' => [
                ],
            ],
            [
                // Validate force latest
                'current_version'     => '10.0.0',
                'force_latest'        => true,
                'expected_migrations' => $migrations_957_to_1000,
            ]
        ];
    }

    /**
     * @dataProvider migrationsProvider
     */
    public function testGetMigrationsToDo(string $current_version, bool $force_latest, array $expected_migrations)
    {
        $class = new \ReflectionClass(\Update::class);
        $method = $class->getMethod('getMigrationsToDo');
        $method->setAccessible(true);

        global $DB;
        $update = new \Update($DB);
        $this->array($method->invokeArgs($update, [$current_version, $force_latest]))->isIdenticalTo($expected_migrations);
    }
}
