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
   die("Sorry. You can't access this file directly");
}

class TicketContract extends CommonItilObject_Item {

   public static $itemtype_1 = 'Ticket';
   public static $items_id_1 = 'tickets_id';

   public static $itemtype_2 = 'Contract';
   public static $items_id_2 = 'contracts_id';
   public static $checkItem_2_Rights = self::HAVE_VIEW_RIGHT_ON_ITEM;

   public static function getTypeName($nb = 0) {
      return _n('fsefesfsfe', 'fsefsefsef', $nb);
   }


   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = 0;
      if (get_class($item) == Ticket::class) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = count(self::getItemsForItem($item));
         }
         return self::createTabEntry(Contract::getTypeName(Session::getPluralNumber()), $nb);
      } else if (get_class($item) == Contract::class) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = count(self::getItemsForItem($item));
         }
         return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);
      } else {
         return '';
      }
   }

   public static function getItemsForItem(CommonDBTM $item) {
      $em = new self();

      return $em->find([
         $item::getForeignKeyField() => $item->getID()
      ]);
   }

   public static function displayTabContentForItem(
      CommonGLPI $item,
      $tabnum = 1,
      $withtemplate = 0
   ) {
      if (!($item instanceof CommonDBTM)) {
         return false;
      }

      $rand = mt_rand();

      if (get_class($item) == Ticket::class) {
         $add_label = __('Add a contract');
         $item_a_fkey = self::$items_id_1;
         $item_b_fkey = self::$items_id_2;
         $linked_itemtype = self::$itemtype_2;
      } else if (get_class($item) == Contract::class) {
         $add_label = __('Add a ticket');
         $item_a_fkey = self::$items_id_2;
         $item_b_fkey = self::$items_id_1;
         $linked_itemtype = self::$itemtype_1;
      }

      $ID = $item->getField('id');

      if (!static::canView() || !$item->can($ID, READ)) {
         return false;
      }

      $canedit = $item->canEdit($ID);

      $linked_items = self::getItemsForItem($item);
      $used    = [];
      $numrows = count($linked_items);
      foreach ($linked_items as $linked_item) {
         $used[$linked_item[$item_b_fkey]] = $linked_item[$item_b_fkey];
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         $form_action = Toolbox::getItemTypeFormURL(__CLASS__);
         echo "<form name='ticketcontract_item_form$rand' id='changeticket_form$rand' method='post' action='$form_action'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . $add_label . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo "<input type='hidden' name='$item_a_fkey' value='$ID'>";
         $linked_itemtype::dropdown([
            'used'        => $used,
            'entity'      => $item->getEntityID(),
            'entity_sons' => $item->isRecursive(),
            'displaywith' => ['id'],
         ]);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = [
            'num_displayed'    => min($_SESSION['glpilist_limit'], $numrows),
            'container'        => 'mass'.__CLASS__.$rand,
            'specific_actions' => [
               'purge' => _x('button', 'Delete permanently'),
            ],
            'extraparams'      => [$item_a_fkey => $item->getID()],
            'width'            => 1000,
            'height'           => 500
         ];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>".$linked_itemtype::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         $header_params = ['ticket_stats' => true];
         $linked_itemtype::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand, $header_params);
         Session::initNavigateListItems(
            $linked_itemtype,
            sprintf(
               __('%1$s = %2$s'),
               $item::getTypeName(1),
               $item->fields["name"]
            )
         );

         $i = 0;
         foreach ($linked_items as $data) {
            Session::addToNavigateListItems($linked_itemtype, $data["id"]);
            $linked_itemtype::showShort($data[$item_b_fkey], [
               'followups'              => false,
               'row_num'                => $i,
               'type_for_massiveaction' => __CLASS__,
               'id_for_massiveaction'   => $data['id'],
               'ticket_stats'           => true,
            ]);
            $i++;
         }
         $linked_itemtype::commonListHeader(Search::HTML_OUTPUT, 'mass'.__CLASS__.$rand, $header_params);
      }
      echo "</table>";
      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

}
