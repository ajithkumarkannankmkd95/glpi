<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class DomainType extends CommonDropdown
{
   static $rightname = 'dropdown';

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return _n('Domain type', 'Domain types', $nb);
   }

   /**
    * @param $ID
    * @param $entity
    * @return integer
    */
   static function transfer($ID, $entity) {
      global $DB;

      if ($ID > 0) {
         // Not already transfer
         // Search init item
         $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['id' => $ID]
         ]);

         if (count($iterator)) {
            $data = $iterator->next();
            $input = [
               'name'         => Toolbox::addslashes_deep($data['name']),
               'entities_id'  => $entity
            ];
            $temp = new self();
            $newID = $temp->getID();

            if ($newID < 0) {
               $newID = $temp->import($input);
            }

            return $newID;
         }
      }
      return 0;
   }
}
