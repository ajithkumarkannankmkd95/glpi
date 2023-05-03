<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

$table = 'glpi_pendingreasons';
// Add new "is_default" field on pendingreasons table
if (!$DB->fieldExists($table, 'is_default')) {
    $migration->addField($table, 'is_default', "tinyint NOT NULL DEFAULT '0'");
}

// Add new "is_pending_per_default" field on pendingreasons table
if (!$DB->fieldExists($table, 'is_pending_per_default')) {
    $migration->addField($table, 'is_pending_per_default', "tinyint NOT NULL DEFAULT '0'");
}

// Add new "calendars_id" field on pendingreasons table
$fkey_to_add = 'calendars_id';
if (!$DB->fieldExists($table, $fkey_to_add)) {
    $migration->addField($table, $fkey_to_add, "int {$default_key_sign} NOT NULL DEFAULT '0'");
    $migration->addKey($table, $fkey_to_add);
}
