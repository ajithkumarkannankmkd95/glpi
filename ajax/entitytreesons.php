<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

$AJAX_INCLUDE = 1;

include ("../inc/includes.php");

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_GET['node'])) {

   if ($_SESSION['glpiactiveprofile']['interface']=='helpdesk') {
      $target = "helpdesk.public.php";
   } else {
      $target = "central.php";
   }

   $nodes = [];

   // Get ancestors of current entity
   $ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);

   // Root node
   if ($_GET['node'] == -1) {
      $pos = 0;

      foreach ($_SESSION['glpiactiveprofile']['entities'] as $entity) {
         $ID                           = $entity['id'];
         $is_recursive                 = $entity['is_recursive'];

         $path = [
            'id'     => 'ent'.$ID,
            'text'   => Dropdown::getDropdownName("glpi_entities", $ID)
         ];
         $path['a_attr']['href'] = $CFG_GLPI["root_doc"]."/front/$target?active_entity=".$ID;

         if ($is_recursive) {
            $path['children'] = true;
            $query2 = "SELECT count(*)
                       FROM `glpi_entities`
                       WHERE `entities_id` = '$ID'";
            $result2 = $DB->query($query2);
            if ($DB->result($result2, 0, 0) > 0) {
               $path['sublink'] = "&nbsp;<a title='".sprintf(__s('%1$s and sub-entities'), $path['text'])."' href='".
                                                 $CFG_GLPI["root_doc"]."/front/".$target.
                                                 "?active_entity=".$ID."&amp;is_recursive=1'>".
                                         "<img alt='".sprintf(__s('%1$s and sub-entities'), $path['text'])."' src='".
                                         $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";
               if (isset($ancestors[$ID])) {
                  $path['state']['opened'] = 'true';
               }
            }
         }
         $nodes[] = $path;
      }
   } else { // standard node
      $node_id = str_replace('ent', '', $_GET['node']);
      $query   = "SELECT *
                  FROM `glpi_entities`
                  WHERE `entities_id` = '$node_id'
                  ORDER BY `name`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($row = $DB->fetch_assoc($result)) {
               $path = [
                  'id'     => 'ent'.$row['id'],
                  'text'   => $row['name']
               ];
               $path['a_attr']['href'] = $CFG_GLPI["root_doc"]."/front/$target?active_entity=".
                                                $row['id'];

               $query2 = "SELECT count(*)
                          FROM `glpi_entities`
                          WHERE `entities_id` = '".$row['id']."'";
               $result2 = $DB->query($query2);
               if ($DB->result($result2, 0, 0) > 0) {
                  $path['children'] = true;
                  $path['sublink']  = "&nbsp;<a title='".sprintf(__s('%1$s and sub-entities'), $path['text'])."' href='".
                                                    $CFG_GLPI["root_doc"]."/front/".$target.
                                                    "?active_entity=".$row['id']."&amp;is_recursive=1'>".
                                            "<img alt='".sprintf(__s('%1$s and sub-entities'), $path['text'])."' src='".
                                            $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";

                  if (isset($ancestors[$row['id']])) {
                     $path['state']['opened'] = 'true';
                  }
               }
               $nodes[] = $path;
            }
         }
      }

   }
   echo json_encode($nodes);
}
