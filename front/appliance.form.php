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

use Glpi\Event;

Session::checkRight('appliance', READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$app = new Appliance();

if (isset($_POST["add"])) {
    $app->check(-1, CREATE, $_POST);

    if ($newID = $app->add($_POST)) {
        Event::log(
            $newID,
            "appliance",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($app->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $app->check($_POST["id"], DELETE);
    $app->delete($_POST);

    Event::log(
        $_POST["id"],
        "appliance",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $app->redirectToList();
} else if (isset($_POST["restore"])) {
    $app->check($_POST["id"], DELETE);

    $app->restore($_POST);
    Event::log(
        $_POST["id"],
        "appliance",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $app->redirectToList();
} else if (isset($_POST["purge"])) {
    $app->check($_POST["id"], PURGE);

    $app->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "appliance",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $app->redirectToList();
} else if (isset($_POST["update"])) {
    $app->check($_POST["id"], UPDATE);

    $app->update($_POST);
    Event::log(
        $_POST["id"],
        "appliance",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $menus = ["management", "appliance"];
    Appliance::displayFullPageForItem($_GET['id'], $menus, [
        'withtemplate' => $_GET['withtemplate']
    ]);
}
