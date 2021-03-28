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

namespace Glpi;

use Ajax;
use CommonDBTM;
use CronTask;
use DBConnection;
use Document;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Infocom;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Event Class
**/
class Event extends CommonDBTM {

   static $rightname = 'logs';



   static function getTypeName($nb = 0) {
      return _n('Log', 'Logs', $nb);
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      if (isset($input['level']) && ($input['level'] <= $CFG_GLPI["event_loglevel"])) {
         return $input;
      }
      return false;
   }

   function post_addItem() {
      //only log in file, important events (connections and critical events; TODO : we need to add a general option to filter this in 9.1)
      if (isset($this->fields['level']) && $this->fields['level'] <= 3) {
         $message_type = "";
         if (isset($this->fields['type']) && $this->fields['type'] != 'system') {
            $message_type = "[".$this->fields['type']." ".$this->fields['id']."] ";
         }

         $full_message = "[".$this->fields['service']."] ".
                         $message_type.
                         $this->fields['level'].": ".
                         Toolbox::stripslashes_deep($this->fields['message'])."\n";

         Toolbox::logInFile("event", $full_message);
      }
   }


   /**
    * Log an event.
    *
    * Log the event $event on the glpi_event table with all the others args, if
    * $level is above or equal to setting from configuration.
    *
    * @param $items_id
    * @param $type
    * @param $level
    * @param $service
    * @param $event
   **/
   static function log($items_id, $type, $level, $service, $event) {
      global $DB;

      $input = ['items_id' => intval($items_id),
                     'type'     => $DB->escape($type),
                     'date'     => $_SESSION["glpi_currenttime"],
                     'service'  => $DB->escape($service),
                     'level'    => intval($level),
                     'message'  => $DB->escape($event)];
      $tmp = new self();
      return $tmp->add($input);
   }


   /**
    * Clean old event - Call by cron
    *
    * @param $day integer
    *
    * @return integer number of events deleted
   **/
   static function cleanOld($day) {
      global $DB;

      $secs = $day * DAY_TIMESTAMP;

      $DB->delete(
         'glpi_events', [
            new \QueryExpression("UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs")
         ]
      );
      return $DB->affectedRows();
   }


   /**
    * Return arrays for function showEvent et lastEvent
   **/
   static function logArray() {

      static $logItemtype = [];
      static $logService  = [];

      if (count($logItemtype)) {
         return [$logItemtype, $logService];
      }

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
                          'login'        => _n('Connection', 'Connections', 1),
                          'setup'        => __('Setup'),
                          'security'     => __('Security'),
                          'reservation'  => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                          'cron'         => CronTask::getTypeName(Session::getPluralNumber()),
                          'document'     => Document::getTypeName(Session::getPluralNumber()),
                          'notification' => _n('Notification', 'Notifications', Session::getPluralNumber()),
                          'plugin'       => _n('Plugin', 'Plugins', Session::getPluralNumber())];

      return [$logItemtype, $logService];
   }


   /**
    * @param $type
    * @param $items_id
   **/
   static function displayItemLogID($type, $items_id) {
      global $CFG_GLPI;

      if (($items_id == "-1") || ($items_id == "0")) {
         echo "&nbsp;";//$item;
      } else {
         switch ($type) {
            case "rules" :
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/rule.generic.form.php?id=".
                     $items_id."\">".$items_id."</a>";
               break;

            case "infocom" :
               $rand = mt_rand();
               echo " <a href='#' data-bs-toggle='modal' data-bs-target='#infocom$rand'>$items_id</a>";
               Ajax::createIframeModalWindow('infocom'.$rand,
                                             Infocom::getFormURLWithID($items_id),
                                             ['height' => 600]);
               break;

            case "devices" :
               echo $items_id;
               break;

            case "reservationitem" :
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?reservationitems_id=".
                     $items_id."\">".$items_id."</a>";
               break;

            default :
               $type = getSingular($type);
               $url  = '';
               if ($item = getItemForItemtype($type)) {
                  $url  =  $item->getFormURLWithID($items_id);
               }
               if (!empty($url)) {
                  echo "<a href=\"".$url."\">".$items_id."</a>";
               } else {
                  echo $items_id;
               }
               break;
         }
      }
   }


   /**
    * Print a nice tab for last event from inventory section
    *
    * Print a great tab to present lasts events occured on glpi
    *
    * @param $user   string  name user to search on message (default '')
    **/
   static function showForUser($user = "") {
      global $DB, $CFG_GLPI;

      // Show events from $result in table form
      list($logItemtype, $logService) = self::logArray();

      // define default sorting
      $usersearch = "";
      if (!empty($user)) {
         $usersearch = $user." ";
      }

      // Query Database
      $iterator = $DB->request([
         'FROM'   => 'glpi_events',
         'WHERE'  => ['message' => ['LIKE', $usersearch . '%']],
         'ORDER'  => 'date DESC',
         'LIMIT'  => (int)$_SESSION['glpilist_limit']
      ]);

      // Number of results
      $number = count($iterator);

      // No Events in database
      if ($number < 1) {
         $twig_params = [
            'class'        => 'tab_cadrehov',
            'header_rows'  => [
               [__('No Event')]
            ],
            'rows'         => []
         ];
         TemplateRenderer::getInstance()->display('components/table.html.twig', $twig_params);
         return;
      }

      $twig_params = [
         'class'        => 'tab_cadre',
         'header_rows'  => [
            [
               [
                  'colspan'   => 5,
                  'content'   => "<a href=\"".$CFG_GLPI["root_doc"]."/front/event.php\">".
                     sprintf(__('Last %d events'), $_SESSION['glpilist_limit'])."</a>"
               ]
            ],
            [
               __('Source'),
               __('Id'),
               _n('Date', 'Dates', 1),
               [
                  'content'   => __('Service'),
                  'style'     => 'width: 10%'
               ],
               [
                  'content'   => __('Message'),
                  'style'     => 'width: 50%'
               ],
            ]
         ],
         'rows'   => []
      ];

      while ($data = $iterator->next()) {
         $items_id = $data['items_id'];
         $type     = $data['type'];
         $date     = $data['date'];
         $service  = $data['service'];
         $message  = $data['message'];

         $itemtype = "&nbsp;";
         if (isset($logItemtype[$type])) {
            $itemtype = $logItemtype[$type];
         } else {
            $type = getSingular($type);
            if ($item = getItemForItemtype($type)) {
               $itemtype = $item->getTypeName(1);
            }
         }

         // Capture the 'echo' output of the function
         ob_start();
         self::displayItemLogID($type, $items_id);
         $item_log_id = ob_get_clean();

         $twig_params['rows'][] = [
            'class'  => 'tab_bg_2',
            'values' => [
               $itemtype,
               $item_log_id,
               Html::convDateTime($date),
               $logService[$service] ?? '',
               $message
            ]
         ];
      }
      TemplateRenderer::getInstance()->display('components/table.html.twig', $twig_params);
   }


   /**
    * Print a nice tab for last event
    *
    * Print a great tab to present lasts events occured on glpi
    *
    * @param string  $target  where to go when complete
    * @param string  $order   order by clause occurences (eg: ) (default 'DESC')
    * @param string  $sort    order by clause occurences (eg: date) (defaut 'date')
    * @param integer $start   (default 0)
   **/
   static function showList($target, $order = 'DESC', $sort = 'date', $start = 0) {
      $DBread = DBConnection::getReadConnection();

      // Show events from $result in table form
      list($logItemtype, $logService) = self::logArray();

      // Columns of the Table
      $items = ["type"     => [__('Source'), ""],
                     "items_id" => [__('ID'), ""],
                     "date"     => [_n('Date', 'Dates', 1), ""],
                     "service"  => [__('Service'), "width='8%'"],
                     "level"    => [__('Level'), "width='8%'"],
                     "message"  => [__('Message'), "width='50%'"]];

      // define default sorting
      if (!isset($items[$sort])) {
         $sort = "date";
      }
      if ($order != "ASC") {
         $order = "DESC";
      }

      // Query Database
      $iterator = $DBread->request([
         'FROM'   => 'glpi_events',
         'ORDER'  => "$sort $order",
         'START'  => (int)$start,
         'LIMIT'  => (int)$_SESSION['glpilist_limit']
      ]);

      // Number of results
      $numrows = countElementsInTable("glpi_events");
      // Get results
      $number = count($iterator);

      // No Events in database
      if ($number < 1) {
         echo "<div class='center b'>".__('No Event')."</div>";
         return;
      }

      // Output events
      $i = 0;

      echo "<div class='center'>";
      $parameters = "sort=$sort&amp;order=$order";
      Html::printPager($start, $numrows, $target, $parameters);

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";

      foreach ($items as $field => $args) {
         echo "<th ".$args[1]."";
         if ($sort == $field) {
            echo " class='order_$order' ";
         }
         echo "><a href='$target?sort=$field&amp;order=".(($order=="ASC")?"DESC":"ASC")."'>".$args[0].
              "</a></th>";
      }
      echo "</tr>";

      while ($row = $iterator->next()) {
         $ID       = $row["id"];
         $items_id = $row["items_id"];
         $type     = $row["type"];
         $date     = $row["date"];
         $service  = $row["service"];
         $level    = $row["level"];
         $message  = $row["message"];

         $itemtype = "&nbsp;";
         if (isset($logItemtype[$type])) {
            $itemtype = $logItemtype[$type];
         } else {
            $type = getSingular($type);
            if ($item = getItemForItemtype($type)) {
               $itemtype = $item->getTypeName(1);
            }
         }

         echo "<tr class='tab_bg_2'>";
         echo "<td>$itemtype</td>";
         echo "<td class='center b'>";
         self::displayItemLogID($type, $items_id);
         echo "</td><td>".Html::convDateTime($date)."</td>";
         echo "<td class='center'>".(isset($logService[$service])?$logService[$service]:$service);
         echo "</td><td class='center'>".$level."</td><td>".$message."</td></tr>";

         $i++;
      }
      echo "</table></div><br>";
   }


   static function getIcon() {
      return "fas fa-scroll";
   }
}
