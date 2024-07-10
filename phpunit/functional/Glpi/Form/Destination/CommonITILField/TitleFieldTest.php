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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use DbTestCase;
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;

final class TitleFieldTest extends DbTestCase
{
    public function testApplyConfiguratedValueToInputUsingAnswers(): void
    {
        $value = 'My custom title';

        $field = new \Glpi\Form\Destination\CommonITILField\TitleField();
        $input = $field->applyConfiguratedValueToInputUsingAnswers(
            configurated_value: $value,
            input             : [],
            answers_set       : new AnswersSet()
        );
        $this->assertEquals('My custom title', $input['name']);
    }

    public function testGetValue(): void
    {
        $form = new Form();
        $form->fields['name'] = "My form title";
        $field = new \Glpi\Form\Destination\CommonITILField\TitleField();

        // Default value
        $generated_value = $field->getValue($form, []);
        $this->assertEquals("My form title", $generated_value);

        // Manual value
        $generated_value = $field->getValue($form, ["title" => "My custom title"]);
        $this->assertEquals("My custom title", $generated_value);
    }
}
