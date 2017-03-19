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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Class to link a certificate to an item
 */
class Certificate_Item extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1    = "Certificate";
   static public $items_id_1    = 'certificates_id';
   static public $take_entity_1 = false;

   static public $itemtype_2    = 'itemtype';
   static public $items_id_2    = 'items_id';
   static public $take_entity_2 = true;

   static $rightname = "certificate";

   /**
    * @since version 0.84
    *
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @param CommonDBTM $item
    */
   static function cleanForItem(CommonDBTM $item) {

      $temp = new self();
      $temp->deleteByCriteria(['itemtype' => $item->getType(),
                               'items_id' => $item->getField('id')]);
   }

   /**
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return array|string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == 'Certificate'
            && count(Certificate::getTypes(false))) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(_n('Associated item', 'Associated items', 2),
                                           self::countForCertificate($item));
            }
            return _n('Associated item', 'Associated items', 2);

         } else if (in_array($item->getType(), Certificate::getTypes(true))
            && Certificate::canView() ) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(Certificate::getTypeName(2),
                                           self::countForItem($item));
            }
            return Certificate::getTypeName(2);
         }
      }
      return '';
   }


   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Certificate') {
         self::showForCertificate($item);
      } else if (in_array($item->getType(), Certificate::getTypes(true))) {
         self::showForItem($item);
      }
      return true;
   }

   /**
    * @param Certificate $item
    * @return int
    */
   static function countForCertificate(Certificate $item) {

      $types = implode("','", $item->getTypes());
      if (empty($types)) {
         return 0;
      }
      return countElementsInTable('glpi_certificates_items',
                                  "`itemtype` IN ('$types')
                                   AND `certificates_id` = '" . $item->getID() . "'");
   }

   /**
    * @param CommonDBTM $item
    * @return int
    */
   static function countForItem(CommonDBTM $item) {
      return countElementsInTable('glpi_certificates_items',
                                  [ 'itemtype' => $item->getType(),
                                    'items_id' => $item->getID()
                                  ]);
   }

   /**
    * @param $certificates_id
    * @param $items_id
    * @param $itemtype
    * @return bool
    */
   function getFromDBbyCertificatesAndItem($certificates_id, $items_id, $itemtype) {
      global $DB;

      $certificate  = new self();
      $certificates = $certificate->find(['certificates_id' => $certificates_id,
                                          'itemtype'        => $itemtype,
                                          'items_id'        => $items_id
                                         ]);
      if (count($certificates) != 1) {
         return false;
      }

      $cert         = current($certificates);
      $this->fields = $cert;

      return true;
   }

   /**
    * @param $values
    */
   function addItem($values) {

      $this->add(['certificates_id' => $values["certificates_id"],
                  'items_id' => $values["items_id"],
                  'itemtype' => $values["itemtype"]]);
   }

   /**
    * @param $certificates_id
    * @param $items_id
    * @param $itemtype
    */
   function deleteItemByCertificatesAndItem($certificates_id, $items_id, $itemtype) {

      if ($this->getFromDBbyCertificatesAndItem($certificates_id, $items_id,
                                                $itemtype)) {
         $this->delete(['id' => $this->fields["id"]]);
      }
   }

   /**
    * Show items links to a certificate
    *
    * @since version 0.84
    *
    * @param $certificate Certificate object
    *
    * @return nothing (HTML display)
    **/
   public static function showForCertificate(Certificate $certificate) {
      global $DB;

      $instID = $certificate->fields['id'];
      if (!$certificate->can($instID, READ)) {
         return false;
      }
      $canedit = $certificate->can($instID, UPDATE);
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
            FROM `glpi_certificates_items`
            WHERE `certificates_id` = '" . $instID . "'
            ORDER BY `itemtype`
            LIMIT " . count(Certificate::getTypes(true));

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if (Session::isMultiEntitiesMode()) {
         $colsup = 1;
      } else {
         $colsup = 0;
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='certificates_form$rand'
                     id='certificates_form$rand'
                     action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='" . ($canedit ? (5 + $colsup) : (4 + $colsup)) . "'>" .
               __('Add an item') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td colspan='" . (3 + $colsup) . "' class='center'>";
         Dropdown::showSelectItemFromItemtypes(
               ['items_id_name'   => 'items_id',
                'itemtypes'       => Certificate::getTypes(true),
                'entity_restrict' => ($certificate->fields['is_recursive']
                                      ? getSonsOf('glpi_entities',
                                       $certificate->fields['entities_id'])
                                       : $certificate->fields['entities_id']),
                'checkright'      => true,
               ]);
         echo "</td><td colspan='2' class='center' class='tab_bg_1'>";
         Html::hidden('certificates_id', ['value' => $instID]);
         echo Html::submit(_x('button', 'Add'), array('name' => 'add'));
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = array();
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" .
            Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }

      echo "<th>" . __('Type') . "</th>";
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . __('Entity') . "</th>";
      }
      echo "<th>" . __('Serial number') . "</th>";
      echo "<th>" . __('Inventory number') . "</th>";
      echo "</tr>";

      for ($i = 0; $i < $number; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");

         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $column = "name";

            $itemTable = getTableForItemType($itemtype);
            $query = " SELECT `" . $itemTable . "`.*,
                              `glpi_certificates_items`.`id` AS items_id,
                              `glpi_entities`.id AS entity "
               . " FROM `glpi_certificates_items`, `" . $itemTable
               . "` LEFT JOIN `glpi_entities`
                     ON (`glpi_entities`.`id` = `" . $itemTable . "`.`entities_id`) "
               . " WHERE `" . $itemTable . "`.`id` = `glpi_certificates_items`.`items_id`
                     AND `glpi_certificates_items`.`itemtype` = '$itemtype'
                     AND `glpi_certificates_items`.`certificates_id` = '$instID' "
               . getEntitiesRestrictRequest(" AND ", $itemTable, '', '', $item->maybeRecursive());

            if ($item->maybeTemplate()) {
               $query .= " AND " . $itemTable . ".is_template='0'";
            }

            $query .= " ORDER BY `glpi_entities`.`completename`, `" . $itemTable . "`.`$column` ";

            if ($result_linked = $DB->query($query)) {
               if ($DB->numrows($result_linked)) {

                  Session::initNavigateListItems($itemtype, Certificate::getTypeName(2) . " = " . $certificate->fields['name']);
                  while ($data = $DB->fetch_assoc($result_linked)) {
                     $item->getFromDB($data["id"]);
                     Session::addToNavigateListItems($itemtype, $data["id"]);
                     $ID = "";
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"]))
                        $ID = " (" . $data["id"] . ")";

                     $link = Toolbox::getItemTypeFormURL($itemtype);
                     $name = "<a href=\"" . $link . "?id=" . $data["id"] . "\">"
                        . $data["name"] . "$ID</a>";

                     echo "<tr class='tab_bg_1'>";

                     if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["items_id"]);
                        echo "</td>";
                     }
                     echo "<td class='center'>" . $item->getTypeName(1) . "</td>";
                     echo "<td class='center' " . (isset($data['is_deleted']) && $data['is_deleted'] ? "class='tab_bg_2_2'" : "") .
                        ">" . $name . "</td>";
                     if (Session::isMultiEntitiesMode()) {
                        echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entity']) . "</td>";
                     }
                     echo "<td class='center'>" . (isset($data["serial"]) ? "" . $data["serial"] . "" : "-") . "</td>";
                     echo "<td class='center'>" . (isset($data["otherserial"]) ? "" . $data["otherserial"] . "" : "-") . "</td>";
                     echo "</tr>";
                  }
               }
            }
         }
      }
      echo "</table>";

      if ($canedit && $number) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($paramsma);
         Html::closeForm();
      }
      echo "</div>";

   }

   /**
    * Show certificates associated to an item
    *
    * @since version 0.84
    *
    * @param $item  CommonDBTM object for which associated certificates must be displayed
    * @param $withtemplate (default '')
    *
    * @return bool
    */
   static function showForItem(CommonDBTM $item, $withtemplate = '') {
      global $DB;

      $ID = $item->getField('id');

      if ($item->isNewID($ID)) {
         return false;
      }
      if (!Session::haveRight("certificate", READ)) {
         return false;
      }

      if (!$item->can($item->fields['id'], READ)) {
         return false;
      }

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }

      $canedit = $item->canAddItem('Certificate');
      $rand = mt_rand();
      $is_recursive = $item->isRecursive();

      $query = "SELECT `glpi_certificates_items`.`id` AS assocID,
                       `glpi_entities`.`id` AS entity,
                       `glpi_certificates`.`name` AS assocName,
                       `glpi_certificates`.*
                FROM `glpi_certificates_items`
                LEFT JOIN `glpi_certificates`
                 ON (`glpi_certificates_items`.`certificates_id`=`glpi_certificates`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_certificates`.`entities_id`=`glpi_entities`.`id`)
                WHERE `glpi_certificates_items`.`items_id` = '$ID'
                      AND `glpi_certificates_items`.`itemtype` = '" . $item->getType() . "' ";

      $query .= getEntitiesRestrictRequest(" AND", "glpi_certificates", '', '', true);

      $query .= " ORDER BY `assocName`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      $certificates = array();
      $certificate = new Certificate();
      $used = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $certificates[$data['assocID']] = $data;
            $used[$data['id']] = $data['id'];
         }
      }

      if ($canedit && $withtemplate < 2) {
         // Restrict entity for knowbase
         $entities = "";
         $entity = $_SESSION["glpiactive_entity"];

         if ($item->isEntityAssign()) {
            /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item->getEntityID() >= 0) {
               $entity = $item->getEntityID();
            }

            if ($item->isRecursive()) {
               $entities = getSonsOf('glpi_entities', $entity);
            } else {
               $entities = $entity;
            }
         }
         $limit = getEntitiesRestrictRequest(" AND ", "glpi_certificates", '', $entities, true);

         $q = "SELECT COUNT(*)
               FROM `glpi_certificates`
               WHERE `is_deleted` = '0'
               $limit";

         $result = $DB->query($q);
         $nb = $DB->result($result, 0, 0);

         echo "<div class='firstbloc'>";

         if (Certificate::canView() && ($nb > count($used))
         ) {
            echo "<form name='certificate_form$rand' id='certificate_form$rand' method='post'
                   action='" . Toolbox::getItemTypeFormURL('Certificate') . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='hidden' name='entities_id' value='$entity'>";
            echo "<input type='hidden' name='is_recursive' value='$is_recursive'>";
            echo "<input type='hidden' name='itemtype' value='" . $item->getType() . "'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            if ($item->getType() == 'Ticket') {
               echo "<input type='hidden' name='tickets_id' value='$ID'>";
            }

            Certificate::dropdownCertificate(array('entity' => $entities,
               'used' => $used));

            echo "</td><td class='center' width='20%'>";
            echo "<input type='submit' name='additem' value=\"" .
               _sx('button', 'Associate a certificate', 'certificates') . "\" class='submit'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number && ($withtemplate < 2)) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = array('num_displayed' => $number);
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      if ($canedit && $number && ($withtemplate < 2)) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . __('Entity') . "</th>";
      }
      echo "<th>" . __('Type') . "</th>";
      echo "<th>" . __('DNS name', 'certificates') . "</th>";
      echo "<th>" . __('DNS suffix', 'certificates') . "</th>";
      echo "<th>" . __('Creation date') . "</th>";
      echo "<th>" . __('Expiration date') . "</th>";
      echo "<th>" . __('Status') . "</th>";
      echo "</tr>";
      $used = array();

      if ($number) {

         Session::initNavigateListItems('Certificate',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(__('%1$s = %2$s'),
               $item->getTypeName(1), $item->getName()));

         foreach ($certificates as $data) {
            $certificateID = $data["id"];
            $link = NOT_AVAILABLE;

            if ($certificate->getFromDB($certificateID)) {
               $link = $certificate->getLink();
            }

            Session::addToNavigateListItems('Certificate', $certificateID);

            $used[$certificateID] = $certificateID;

            echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
            if ($canedit && ($withtemplate < 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
               echo "</td>";
            }
            echo "<td class='center'>$link</td>";
            if (Session::isMultiEntitiesMode()) {
               echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entities_id']) .
                  "</td>";
            }
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_certificatetypes",
               $data["certificatetypes_id"]);
            echo "</td>";
            echo "<td class='center'>" . $data["dns_name"] . "</td>";
            echo "<td class='center'>" . $data["dns_suffix"] . "</td>";
            echo "<td class='center'>" . Html::convDate($data["date_creation"]) . "</td>";
            if ($data["date_expiration"] <= date('Y-m-d')
               && !empty($data["date_expiration"])
            ) {
               echo "<td class='center'>";
               echo "<div class='deleted'>" . Html::convDate($data["date_expiration"]) . "</div>";
               echo "</td>";
            } else if (empty($data["date_expiration"])) {
               echo "<td class='center'>" . __('Does not expire', 'certificates') . "</td>";
            } else {
               echo "<td class='center'>" . Html::convDate($data["date_expiration"]) . "</td>";
            }
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_certificatestates",
               $data["certificatestates_id"]);
            echo "</td>";
            echo "</tr>";
            $i++;
         }
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate < 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }
}
