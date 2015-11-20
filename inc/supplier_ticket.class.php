<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class Supplier_Ticket
 *
 * @since version 0.84
**/
class Supplier_Ticket extends CommonITILActor {

   // From CommonDBRelation
   static public $itemtype_1 = 'Ticket';
   static public $items_id_1 = 'tickets_id';
   static public $itemtype_2 = 'Supplier';
   static public $items_id_2 = 'suppliers_id';

   function post_addItem() {
      $ticket = new Ticket();
      $ticket->getFromDB($this->fields['tickets_id']);
      NotificationEvent::raiseEvent("assignedtosupplier", $ticket);

      parent::post_addItem();
   }


   /**
    * @param $items_id
    * @param $email
    *
    * @since version 0.85
   **/
   function isSupplierEmail($items_id, $email) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_suppliers`
                      ON (`".$this->getTable()."`.`suppliers_id` = `glpi_suppliers`.`id`)
                WHERE `".$this->getTable()."`.`tickets_id` = '".$items_id."'
                      AND `glpi_suppliers`.`email` = '$email'";

      foreach ($DB->request($query) as $data) {
         return true;
      }
      return false;
   }
  
}
?>
