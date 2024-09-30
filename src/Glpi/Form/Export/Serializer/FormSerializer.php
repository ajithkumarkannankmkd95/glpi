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

namespace Glpi\Form\Export\Serializer;

use Entity;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Context\ConfigWithForeignKeysInterface;
use Glpi\Form\Export\Result\ExportResult;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Result\ImportResult;
use Glpi\Form\Export\Result\ImportResultPreview;
use Glpi\Form\Export\Specification\AccesControlPolicyContentSpecification;
use Glpi\Form\Export\Specification\ExportContentSpecification;
use Glpi\Form\Export\Specification\FormContentSpecification;
use Glpi\Form\Export\Specification\SectionContentSpecification;
use Glpi\Form\Form;
use Glpi\Form\Section;
use InvalidArgumentException;
use RuntimeException;
use Session;

final class FormSerializer extends AbstractFormSerializer
{
    public function getVersion(): int
    {
        return 1;
    }

    /** @param array<Form> $forms */
    public function exportFormsToJson(array $forms): ExportResult
    {
        $export_specification = new ExportContentSpecification();
        $export_specification->version = $this->getVersion();

        foreach ($forms as $form) {
            // Add forms to the main export spec
            $form_spec = $this->exportFormToSpec($form);
            $export_specification->addForm($form_spec);
        }

        return new ExportResult(
            filename: $this->computeJsonFileName($forms),
            json_content: $this->serialize($export_specification),
        );
    }

    public function previewImport(
        string $json,
        DatabaseMapper $mapper,
    ): ImportResultPreview {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new InvalidArgumentException("Unsupported version");
        }

        // Validate each forms
        $results = new ImportResultPreview();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            $form_name = $form_spec->name;
            if ($mapper->validateRequirements($requirements)) {
                $results->addValidForm($form_name);
            } else {
                $results->addInvalidForm($form_name);
            }
        }

        return $results;
    }

    public function importFormsFromJson(
        string $json,
        DatabaseMapper $mapper,
    ): ImportResult {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new InvalidArgumentException("Unsupported version");
        }

        // Import each forms
        $result = new ImportResult();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            if (!$mapper->validateRequirements($requirements)) {
                $result->addFailedFormImport(
                    $form_spec->name,
                    ImportError::MISSING_DATA_REQUIREMENT
                );
                continue;
            }

            $form = $this->importFormFromSpec($form_spec, $mapper);
            $result->addImportedForm($form);
        }

        return $result;
    }

    /** @param array<Form> $forms */
    private function computeJsonFileName(array $forms): string
    {
        $date = Session::getCurrentDate();

        if (count($forms) === 1) {
            $form = current($forms);
            $formatted_name = \Toolbox::slugify($form->fields['name']);
            $filename = "$formatted_name-$date";
        } else {
            // When exporting multiple forms, we compute an additionnal checksum
            // to make sure two different exports with the same number of forms
            // have a different file name.
            $ids = array_map(fn (Form $form) => $form->getID(), $forms);
            $checksum = crc32(json_encode($ids));

            $nb = count($forms);
            $filename = "export-of-$nb-forms-$date-$checksum";
        }

        return $filename . ".json";
    }

    private function exportFormToSpec(Form $form): FormContentSpecification
    {
        // TODO: questions, ...
        $form_spec = $this->exportBasicFormProperties($form);
        $form_spec = $this->exportSections($form, $form_spec);
        $form_spec = $this->exportAccesControlPolicies($form, $form_spec);

        return $form_spec;
    }

    private function importFormFromSpec(
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        /** @var \DBmysql $DB */
        global $DB;

        $use_transaction = !$DB->inTransaction();

        if ($use_transaction) {
            $DB->beginTransaction();
            $forms = $this->doImportFormFormSpecs($form_spec, $mapper);
            $DB->commit();
        } else {
            $forms = $this->doImportFormFormSpecs($form_spec, $mapper);
        }

        return $forms;
    }

    private function doImportFormFormSpecs(
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        // TODO: questions, ...
        $form = $this->importBasicFormProperties($form_spec, $mapper);
        $form = $this->importSections($form, $form_spec);
        $form = $this->importAccessControlPolicices($form, $form_spec, $mapper);

        return $form;
    }

    private function extractDataRequirementsFromSerializedJsonConfig(
        array $fkeys_handlers,
        array $serialized_data,
    ): array {
        $requirements = [];
        foreach ($fkeys_handlers as $fkey_handler) {
            array_push(
                $requirements,
                ...$fkey_handler->getDataRequirements($serialized_data)
            );
        }

        return $requirements;
    }

    private function replaceForeignKeysByNameInSerializedJsonConfig(
        array $fkeys_handlers,
        array $serialized_data,
    ): array {
        foreach ($fkeys_handlers as $fkey_handler) {
            $serialized_data = $fkey_handler->replaceForeignKeysByNames($serialized_data);
        }

        return $serialized_data;
    }

    private function replaceNamesByForeignKeysInSerializedJsonConfig(
        array $fkeys_handlers,
        array $serialized_data,
        DatabaseMapper $mapper,
    ): array {
        foreach ($fkeys_handlers as $fkey_handler) {
            $serialized_data = $fkey_handler->replaceNamesByForeignKeys($serialized_data, $mapper);
        }

        return $serialized_data;
    }

    private function exportBasicFormProperties(
        Form $form
    ): FormContentSpecification {
        $spec               = new FormContentSpecification();
        $spec->name         = $form->fields['name'];
        $spec->header       = $form->fields['header'] ?? "";
        $spec->is_recursive = $form->fields['is_recursive'];

        $entity = Entity::getById($form->fields['entities_id']);
        $spec->entity_name = $entity->fields['name'];
        $spec->addDataRequirement(Entity::class, $entity->fields['name']);

        return $spec;
    }

    private function importBasicFormProperties(
        FormContentSpecification $spec,
        DatabaseMapper $mapper,
    ): Form {
        // Get ids from mapper
        $entities_id = $mapper->getItemId(Entity::class, $spec->entity_name);

        $form = new Form();
        $id = $form->add([
            'name'                  => $spec->name,
            'header'                => $spec->header,
            'entities_id'           => $entities_id,
            'is_recursive'          => $spec->is_recursive,
            '_do_not_init_sections' => true,
        ]);
        if (!$form->getFromDB($id)) {
            throw new RuntimeException("Failed to create form");
        }

        return $form;
    }

    private function exportSections(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getSections() as $section) {
            $section_spec = new SectionContentSpecification();
            $section_spec->name = $section->fields['name'];
            $section_spec->rank = $section->fields['rank'];
            $section_spec->description = $section->fields['description'] ?? "";

            $form_spec->sections[] = $section_spec;
        }

        return $form_spec;
    }

    private function importSections(
        Form $form,
        FormContentSpecification $form_spec,
    ): Form {
        /** @var SectionContentSpecification $section_spec */
        foreach ($form_spec->sections as $section_spec) {
            $section = new Section();
            $id = $section->add([
                'name'        => $section_spec->name,
                'description' => $section_spec->description,
                'rank'        => $section_spec->rank,
                Form::getForeignKeyField() => $form->fields['id'],
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create section");
            }
        };

        // Reload to clear lazy loaded data
        $form->getFromDB($form->getId());
        return $form;
    }

    private function exportAccesControlPolicies(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getAccessControls() as $policy) {
            // Compute strategy and config
            $strategy = $policy->getStrategy()::class;
            $config = $policy->getConfig();

            // Read simple fields
            $policy_spec = new AccesControlPolicyContentSpecification();
            $policy_spec->strategy = $strategy;
            $policy_spec->is_active = $policy->fields['is_active'];

            // Serialize config
            $serialized_config = $config->jsonSerialize();
            if ($config instanceof ConfigWithForeignKeysInterface) {
                $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                    $config::listForeignKeysHandlers(),
                    $serialized_config
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $serialized_config = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                    $config::listForeignKeysHandlers(),
                    $serialized_config,
                );
            }
            $policy_spec->config_data = $serialized_config;

            // Add to form spec
            $form_spec->policies[] = $policy_spec;
        }

        return $form_spec;
    }

    private function importAccessControlPolicices(
        Form $form,
        FormContentSpecification $spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($spec->policies as $policy_spec) {
            $policy = new FormAccessControl();

            // Load strategy
            $strategy_class = $policy_spec->strategy;
            if (!$policy->isValidStrategy($strategy_class)) {
                throw new InvalidArgumentException();
            }
            $strategy = new $strategy_class();

            $config_class = $strategy->getConfigClass();
            $serialized_config = $policy_spec->config_data;
            if (is_a($config_class, ConfigWithForeignKeysInterface::class, true)) {
                $serialized_config = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                    $config_class::listForeignKeysHandlers(),
                    $serialized_config,
                    $mapper
                );
            }
            $config = $config_class::jsonDeserialize($serialized_config);

            // Insert data
            $id = $policy->add([
                'strategy'  => $strategy_class,
                'is_active' => $policy_spec->is_active,
                '_config'   => $config,
                Form::getForeignKeyField() => $form->getID(),
            ]);

            if (!$id) {
                throw new RunTimeException("Failed to create access control");
            }
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }
}
