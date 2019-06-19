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

namespace Glpi;

use \Ajax;
use \CommonDBTM;
use \Html;
use \Session;
use \Toolbox;
use \Infocom;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Event Class
 * Internal event logging for GLPI services.
 * 
 * Starting in 10.0.0, any event added using this class is translated and added using SIEMEvent.
 * This class remains to enforce normalization of internal event data but the glpi_events table is not used.
**/
class Event extends CommonDBTM {

   static $rightname = 'logs';



   static function getTypeName($nb = 0) {
      return _n('Log', 'Logs', $nb);
   }

   function prepareInputForAdd($input) {

      Toolbox::deprecated('GLPI Events should be logged using the log function since version 10.0.0. Plugins should add events through SIEMEvent.');
      // Deny any attempt to add an event to the legacy table
      return false;
   }


   /**
    * Log an event.
    *
    * Log the event $event to the internal SIEM system as an {@link \SIEMEvent} if
    * $level is above or equal to setting from configuration.
    *
    * @param $items_id The id of the related item
    * @param $type The type of the related item
    * @param $level The level of the event
    * @param $service The module/service that generated the event
    * @param $event The name of the event
    * @param $extrainfo Array of extra event properties
    * @param $significance int The significance of the event (0 = Information, 1 = Warning, 2 = Exception).
    *    Default is Information.
   **/
   static function log($items_id, $type, $level, $service, $event, $extrainfo = [], int $significance = \SIEMEvent::INFORMATION) {
      global $CFG_GLPI;

      // Only log if the event's level is the same or lower than the setting from configuration
      if (!($level <= $CFG_GLPI["event_loglevel"])) {
         return false;
      }

      if (isset($extrainfo['_correlation_id'])) {
         $correlation_id = $extrainfo['_correlation_id'];
         unset($extrainfo['_correlation_id']);
      } else {
         $correlation_id = null;
      }

      $input = [
         'name'      => $event,
         'content'   => json_encode([
            'type'      => $type,
            'items_id'  => intval($items_id),
            'service'   => $service,
            'level'     => $level
         ] + $extrainfo),
         'significance' => $significance,
         'date'      => $_SESSION["glpi_currenttime"],
         'correlation_id'   => $correlation_id
      ];

      $tmp = new \SIEMEvent();
      return $tmp->add($input);
   }


   /**
    * Clean old event - Call by cron
    *
    * @deprecated 10.0.0
    * @param $day integer
    *
    * @return integer number of events deleted
    * @todo Integrate log cleanup system into SIEMEvent
   **/
   static function cleanOld($day) {
      global $DB;

      $secs = $day * DAY_TIMESTAMP;

      $result = $DB->delete(
         'glpi_events', [
            new \QueryExpression("UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs")
         ]
      );
      return $result->rowCount();
   }

   public static function translateEventName($name) {
      switch ($name) {
         case 'sensor_fault':
            return __('Sensor fault');
         default:
            return $name;
      }
   }

   /**
    * Attempt to translate event properties
    * @since 10.0.0
    * @param array $properties
    * @return void
    */
   public static function translateEventProperties(array &$properties) {
      static $logItemtype = [];
      static $logService  = [];

      $logItemtype = ['system'      => __('System'),
                           'devices'     => _n('Component', 'Components', Session::getPluralNumber()),
                           'planning'    => __('Planning'),
                           'reservation' => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                           'dropdown'    => _n('Dropdown', 'Dropdowns', Session::getPluralNumber()),
                           'rules'       => _n('Rule', 'Rules', Session::getPluralNumber())];

      $logService = ['inventory'    => __('Assets'),
                          'tracking'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                          'maintain'     => __('Assistance'),
                          'planning'     => __('Planning'),
                          'tools'        => __('Tools'),
                          'financial'    => __('Management'),
                          'login'        => __('Connection'),
                          'setup'        => __('Setup'),
                          'security'     => __('Security'),
                          'reservation'  => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                          'cron'         => _n('Automatic action', 'Automatic actions', Session::getPluralNumber()),
                          'document'     => _n('Document', 'Documents', Session::getPluralNumber()),
                          'notification' => _n('Notification', 'Notifications', Session::getPluralNumber()),
                          'plugin'       => _n('Plugin', 'Plugins', Session::getPluralNumber())];

      $otherFields = [
         'login_name'         => __('Login'),
         'level'              => __('Level'),
         'source_ip'          => __('Source IP'),
         'items_id'           => __('Items ID'),
         'itemtype'           => __('Item type'),
         'previous_revision'  => __('Previous revision'),
         'next_revision'      => __('Next revision'),
      ];

      if (array_key_exists('type', $properties)) {
         $properties['type']['name'] = __('Source');
         if (isset($properties['type']['value'])) {
            if (isset($logItemtype[$properties['type']['value']])) {
               $properties['type']['value'] = $logItemtype[$properties['type']['value']];
            } else {
               $type = getSingular($properties['type']['value']);
               if ($item = getItemForItemtype($type)) {
                  $itemtype = $item->getTypeName(1);
                  $properties['type']['value'] = $itemtype;
               }
            }
         }
      }
      if (array_key_exists('service', $properties)) {
         $properties['service']['name'] = __('Service');
         if (isset($properties['service']['value'])) {
            if (isset($logService[$properties['service']['value']])) {
               $properties['service']['value'] = $logService[$properties['service']['value']];
            }
         }
      }

      foreach ($otherFields as $fieldname => $localname) {
         if (array_key_exists($fieldname, $properties)) {
            $properties[$fieldname]['name'] = $localname;
         }
      }
   }

   public static function logItemAction(string $action, string $itemtype, int $items_id, string $service, string $login_name) {
      $name = '';
      switch ($action) {
         case 'add':
            $name = __('%1$s adds the item %2$s');
            break;
         case 'update':
            $name = __('%1$s updates the item %2$s');
            break;
         case 'delete':
            $name = __('%1$s deletes the item %2$s');
            break;
         case 'purge':
            $name = __('%1$s purges the item %2$s');
            break;
         case 'restore':
            $name = __('%1$s restores the item %2$s');
            break;
         default:
            return false;
      }
      self::log($items_id, $itemtype, 4, $service, sprintf($name, $login_name, $items_id));
   }
}
