<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

/**
 * Item_TicketRecurrent Class
 *
 *  Relation between TicketRecurrents and Items
 **/
class Item_TicketRecurrent extends CommonItilObject_Item
{
   // From CommonDBRelation
    public static $itemtype_1          = 'TicketRecurrent';
    public static $items_id_1          = 'ticketrecurrents_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public static function getTypeName($nb = 0)
    {
        return _n('Ticket recurrent item', 'Ticket recurrent items', $nb);
    }

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
    public static function canAddRelatedItem($ticketrecurrent, $type = '')
    {
        // no reason to bloc adding item
        return true;
    }

    public function prepareInputForAdd($input)
    {

       // Avoid duplicate entry
        if (
            countElementsInTable($this->getTable(), ['ticketrecurrents_id' => $input['ticketrecurrents_id'],
                'itemtype'   => $input['itemtype'],
                'items_id'   => $input['items_id']
            ]) > 0
        ) {
            return false;
        }

        $ticketrecurrent = new TicketRecurrent();
        $ticketrecurrent->getFromDB($input['ticketrecurrents_id']);

       // Get item location if location is not already set in ticket
        if (empty($ticketrecurrent->fields['locations_id'])) {
            if (($input["items_id"] > 0) && !empty($input["itemtype"])) {
                if ($item = getItemForItemtype($input["itemtype"])) {
                    if ($item->getFromDB($input["items_id"])) {
                        if ($item->isField('locations_id')) {
                             $ticketrecurrent->fields['_locations_id_of_item'] = $item->fields['locations_id'];

                             // Process Business Rules
                             $rules = new RuleTicketCollection($ticketrecurrent->fields['entities_id']);

                             $ticketrecurrent->fields = $rules->processAllRules(
                                 Toolbox::stripslashes_deep($ticketrecurrent->fields),
                                 Toolbox::stripslashes_deep($ticketrecurrent->fields),
                                 ['recursive' => true]
                             );

                               unset($ticketrecurrent->fields['_locations_id_of_item']);
                               $ticketrecurrent->updateInDB(['locations_id']);
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
    * @param $ticketrecurrent ticketrecurrent object
    * @param $options   array of possible options:
    *    - id                  : ID of the ticketrecurrent
    *    - _users_id_requester : ID of the requester user
    *    - items_id            : array of elements (itemtype => array(id1, id2, id3, ...))
    *
    * @return void
   **/
    public static function itemAddForm($ticketrecurrent, $options = [])
    {
        if ($options['id'] ?? 0 > 0) {
            // Get requester
            $class        = new $ticketrecurrent->userlinkclass();
            $ticketrecurrents_user = $class->getActors($options['id']);
            if (
                isset($ticketrecurrents_user[CommonITILActor::REQUESTER])
                && (count($ticketrecurrents_user[CommonITILActor::REQUESTER]) == 1)
            ) {
                foreach ($ticketrecurrents_user[CommonITILActor::REQUESTER] as $user_id_single) {
                    $options['_users_id_requester'] = $user_id_single['users_id'];
                }
            }
        }
        parent::itemAddForm($ticketrecurrent, $options);
    }

    /**
     * Print the HTML array for Items linked to a ticketrecurrent
     *
     * @param $ticketrecurrent ticketrecurrent object
     *
     * @return void
     **/
    public static function showForTicket(TicketRecurrent $ticketrecurrent)
    {
        Toolbox::deprecated();
        static::showForObject($ticketrecurrent);
    }

    public static function showForObject($ticketrecurrent, $options = [])
    {
        // Get requester
        $class        = new $ticketrecurrent->userlinkclass();
        $ticketrecurrents_user = $class->getActors($ticketrecurrent->fields['id']);
        $options['_users_id_requester'] = 0;
        if (
            isset($ticketrecurrents_user[CommonITILActor::REQUESTER])
            && (count($ticketrecurrents_user[CommonITILActor::REQUESTER]) == 1)
        ) {
            foreach ($ticketrecurrents_user[CommonITILActor::REQUESTER] as $user_id_single) {
                $options['_users_id_requester'] = $user_id_single['users_id'];
            }
        }
        return parent::showForObject($ticketrecurrent, $options);
    }
}
