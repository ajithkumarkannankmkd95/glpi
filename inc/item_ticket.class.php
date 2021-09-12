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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Item_Ticket Class
 *
 *  Relation between Tickets and Items
**/
class Item_Ticket extends CommonItilObject_Item {


   // From CommonDBRelation
   static public $itemtype_1          = 'Ticket';
   static public $items_id_1          = 'tickets_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

   /**
    * to add condition on the object 1 
    *
    * @since 0.83
    *
    * @param Ticket $ticket ticket 
    * @param string $type of the object to add
    *
    * @return boolean
   **/
   function canAddItem($ticket, $type = '') {

      return !in_array($ticket->fields['status'], array_merge($ticket->getClosedStatusArray(),
      $ticket->getSolvedStatusArray()));
   }

   function prepareInputForAdd($input) {

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(), ['tickets_id' => $input['tickets_id'],
                                                   'itemtype'   => $input['itemtype'],
                                                   'items_id'   => $input['items_id']]) > 0) {
         return false;
      }

      $ticket = new Ticket();
      $ticket->getFromDB($input['tickets_id']);

      // Get item location if location is not already set in ticket
      if (empty($ticket->fields['locations_id'])) {
         if (($input["items_id"] > 0) && !empty($input["itemtype"])) {
            if ($item = getItemForItemtype($input["itemtype"])) {
               if ($item->getFromDB($input["items_id"])) {
                  if ($item->isField('locations_id')) {
                     $ticket->fields['_locations_id_of_item'] = $item->fields['locations_id'];

                     // Process Business Rules
                     $rules = new RuleTicketCollection($ticket->fields['entities_id']);

                     $ticket->fields = $rules->processAllRules(Toolbox::stripslashes_deep($ticket->fields),
                                                Toolbox::stripslashes_deep($ticket->fields),
                                                ['recursive' => true]);

                     unset($ticket->fields['_locations_id_of_item']);
                     $ticket->updateInDB(['locations_id']);
                  }
               }
            }
         }
      }

      return parent::prepareInputForAdd($input);
   }


   /**
    * Print the HTML ajax associated item add
    *
    * @param $ticket Ticket object
    * @param $options   array of possible options:
    *    - id                  : ID of the ticket
    *    - _users_id_requester : ID of the requester user
    *    - items_id            : array of elements (itemtype => array(id1, id2, id3, ...))
    *
    * @return void
   **/
   static function itemAddForm( $ticket, $options = []) {
      if ($params['id'] > 0) {
         // Get requester
         $class        = new $ticket->userlinkclass();
         $tickets_user = $class->getActors($params['id']);
         if (isset($tickets_user[CommonITILActor::REQUESTER])
            && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)) {
            foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
               $options['_users_id_requester'] = $user_id_single['users_id'];
            }
         }
      }
      parent::itemAddForm($ticket, $options);
   }



   /**
    * Print the HTML array for Items linked to a ticket
    *
    * @param $ticket Ticket object
    *
    * @return void
   **/
   static function showForObject( $ticket, $options = []) {
      // Get requester
      $class        = new $ticket->userlinkclass();
      $tickets_user = $class->getActors($ticket->fields['id']);
      $options['_users_id_requester'] = 0;
      if (isset($tickets_user[CommonITILActor::REQUESTER])
              && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)) {
         foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
            $options['_users_id_requester'] = $user_id_single['users_id'];
         }
      }
      return parent::showForObject($ticket, $options);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Ticket' :
            self::showForObject($item);
            break;
      }
      return true;
   }

}
