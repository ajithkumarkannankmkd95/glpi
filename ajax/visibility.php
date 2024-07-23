<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
 * @var array $CFG_GLPI
 */
global $CFG_GLPI;

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "visibility.php")) {
    /** @var $this \Glpi\Controller\LegacyFileLoadController */
    $this->setAjax();

    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkCentralAccess();

if (isset($_POST['type']) && !empty($_POST['type'])) {
    $display = false;
    $rand    = mt_rand();
    $prefix = '';
    $suffix = '';
    if (isset($_POST['prefix']) && !empty($_POST['prefix'])) {
        $prefix = $_POST['prefix'] . '[';
        $suffix = ']';
    } else {
        $_POST['prefix'] = '';
    }

    echo "<table class='tab_format'><tr>";
    switch ($_POST['type']) {
        case 'User':
            echo "<td>";
            $params =
            $params = ['name' => $prefix . 'users_id' . $suffix];
            if (isset($_POST['right'])) {
                $params['right'] = isset($_POST['allusers']) ? 'all' : $_POST['right'];
            }
            if (isset($_POST['entity']) && $_POST['entity'] >= 0) {
                $params['entity'] = $_POST['entity'];
                if (isset($_POST['entity_sons'])) {
                    $params['entity_sons'] = $_POST['entity_sons'];
                }
            }

            User::dropdown($params);
            echo "</td>";
            $display = true;
            break;

        case 'Group':
            echo "<td>";
            $params             = ['rand' => $rand,
                'name' => $prefix . 'groups_id' . $suffix
            ];
            $params['toupdate'] = ['value_fieldname'
                                                  => 'value',
                'to_update'  => "subvisibility$rand",
                'url'        => $CFG_GLPI["root_doc"] . "/ajax/subvisibility.php",
                'moreparams' => ['items_id' => '__VALUE__',
                    'type'     => $_POST['type'],
                    'prefix'   => $_POST['prefix']
                ]
            ];
            if (isset($_POST['entity']) && $_POST['entity'] >= 0) {
                $params['entity'] = $_POST['entity'];
                $params['toupdate']['moreparams']['entity'] = $_POST['entity'];
                if (isset($_POST['entity_sons'])) {
                    $params['entity_sons'] = $_POST['entity_sons'];
                    $params['toupdate']['moreparams']['entity_sons'] = $_POST['entity_sons'];
                }
            }

            Group::dropdown($params);
            echo "</td><td>";
            echo "<span id='subvisibility$rand'></span>";
            echo "</td>";
            $display = true;
            break;

        case 'Entity':
            echo "<td>";
            Entity::dropdown([
                'value'       => $_SESSION['glpiactive_entity'],
                'name'        => $prefix . 'entities_id' . $suffix,
                'entity'      => $_POST['entity'] ?? -1,
                'entity_sons' => $_POST['is_recursive'] ?? false,
            ]);
            echo "</td><td>";
            echo __s('Child entities');
            echo "</td><td>";
            Dropdown::showYesNo($prefix . 'is_recursive' . $suffix);
            echo "</td>";
            $display = true;
            break;

        case 'Profile':
            echo "<td>";
            $checkright   = (READ | CREATE | UPDATE | PURGE);
            $righttocheck = $_POST['right'];
            if ($_POST['right'] == 'faq') {
                $righttocheck = 'knowbase';
                $checkright   = KnowbaseItem::READFAQ;
            }
            $params             = [
                'rand'      => $rand,
                'name'      => $prefix . 'profiles_id' . $suffix,
                'condition' => [
                    'glpi_profilerights.name'     => $righttocheck,
                    'glpi_profilerights.rights'   => ['&', $checkright]
                ]
            ];
            $params['toupdate'] = ['value_fieldname'
                                                  => 'value',
                'to_update'  => "subvisibility$rand",
                'url'        => $CFG_GLPI["root_doc"] . "/ajax/subvisibility.php",
                'moreparams' => ['items_id' => '__VALUE__',
                    'type'     => $_POST['type'],
                    'prefix'   => $_POST['prefix']
                ]
            ];

            Profile::dropdown($params);
            echo "</td><td>";
            echo "<span id='subvisibility$rand'></span>";
            echo "</td>";
            $display = true;
            break;
    }

    if ($display && (!isset($_POST['nobutton']) || !$_POST['nobutton'])) {
        echo "<td><input type='submit' name='addvisibility' value=\"" . _sx('button', 'Add') . "\"
                   class='btn btn-primary'></td>";
    } else {
       // For table w3c
        echo "<td>&nbsp;</td>";
    }
    echo "</tr></table>";
}
