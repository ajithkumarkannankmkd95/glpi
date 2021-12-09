<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 */
class DbEngine extends AbstractRequirement {

   /**
    * DB instance.
    *
    * @var \DBmysql
    */
   private $db;

   public function __construct(\DBmysql $db) {
      $this->title = __('DB engine version');
      $this->db = $db;
   }

   protected function check() {
      $version_string = $this->db->getVersion();

      $server  = preg_match('/-MariaDB/', $version_string) ? 'MariaDB': 'MySQL';
      $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $version_string);

      $is_supported = $server === 'MariaDB'
         ? version_compare($version, '10.2', '>=')
         : version_compare($version, '5.7', '>=');

      if ($is_supported) {
         $this->validated = true;
         $this->validation_messages[] = sprintf(
            __('Database engine version (%s) is supported.'),
            $version
         );
      } else {
         $this->validated = false;
         $this->validation_messages[] = sprintf(
            __('Database engine version (%s) is not supported.'),
            $version
         );
      }
   }
}
