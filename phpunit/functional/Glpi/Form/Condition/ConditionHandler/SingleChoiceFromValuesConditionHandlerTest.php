<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\Condition\ConditionHandler;

use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class SingleChoiceFromValuesConditionHandlerTest extends AbstractConditionHandler
{
    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        $type = QuestionTypeRadio::class;
        $extra_data = new QuestionTypeSelectableExtraDataConfig(
            options: [
                "Option A",
                "Option B",
                "Option C",
                "Option D",
            ]
        );

        // Test with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "Option C",
            'submitted_answer'    => "Option B",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "Option C",
            'submitted_answer'    => "Option C",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test with the NOT EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "Option C",
            'submitted_answer'    => "Option B",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "Option C",
            'submitted_answer'    => "Option C",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
    }
}
