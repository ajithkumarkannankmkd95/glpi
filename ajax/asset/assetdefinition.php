<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\CustomFieldDefinition;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;

/** @var \Glpi\Controller\LegacyFileLoadController $this */
$this->setAjax();

Session::checkRight(AssetDefinition::$rightname, READ);
Session::writeClose();

if ($_REQUEST['action'] === 'get_all_fields') {
    header("Content-Type: application/json; charset=UTF-8");
    $definition = new AssetDefinition();
    if (!$definition->getFromDB($_GET['assetdefinitions_id'])) {
        throw new NotFoundHttpException();
    }
    $all_fields = $definition->getAllFields();
    $field_results = [];
    foreach ($all_fields as $k => $v) {
        if (!empty($_POST['searchText']) && stripos($v['text'], $_POST['searchText']) === false) {
            continue;
        }
        $v['id'] = $k;
        $field_results[] = $v;
    }
    echo json_encode([
        'results' => $field_results,
        'count' => count($all_fields)
    ], JSON_THROW_ON_ERROR);
    return;
} else if ($_REQUEST['action'] === 'get_field_placeholder' && isset($_POST['fields']) && is_array($_POST['fields'])) {
    header("Content-Type: application/json; charset=UTF-8");
    $custom_field = new CustomFieldDefinition();
    $results = [];
    foreach ($_POST['fields'] as $field) {
        if ($field['customfields_id'] > 0) {
            if (!$custom_field->getFromDB($field['customfields_id'])) {
                throw new NotFoundHttpException();
            }
        } else {
            $custom_field->fields['system_name'] = '';
            $custom_field->fields['label'] = $field['label'];
            $custom_field->fields['type'] = $field['type'];
            $custom_field->fields['itemtype'] = 'Computer'; // Doesn't matter what it is as long as it's not empty
            $custom_field->fields['default_value'] = '';

            $asset_definition = new AssetDefinition();
            if (!$asset_definition->getFromDB($field['assetdefinitions_id'])) {
                throw new NotFoundHttpException();
            }
            $fields_display = $asset_definition->getDecodedFieldsField();
            foreach ($fields_display as $field_display) {
                if ($field_display['key'] === $field['key']) {
                    $custom_field->fields['field_options'] = $field_display['field_options'] ?? [];
                    break;
                }
            }
        }
        $custom_field->fields['field_options'] = array_merge($custom_field->fields['field_options'] ?? [], $field['field_options'] ?? []);
        $custom_field->fields['field_options']['disabled'] = true;
        $results[$field['key']] = $custom_field->getFieldType()->getFormInput('', null);
    }
    echo json_encode($results, JSON_THROW_ON_ERROR);
    return;
} else if ($_REQUEST['action'] === 'get_core_field_editor') {
    header("Content-Type: text/html; charset=UTF-8");
    $asset_definition = new AssetDefinition();
    if (!$asset_definition->getFromDB($_GET['assetdefinitions_id'])) {
        throw new NotFoundHttpException();
    }
    $asset_definition->showFieldOptionsForCoreField($_GET['key'], $_GET['field_options'] ?? []);
    return;
}
throw new BadRequestHttpException();
