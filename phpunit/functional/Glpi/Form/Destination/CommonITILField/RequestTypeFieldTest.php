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
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\RequestTypeField;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Ticket;

final class RequestTypeFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testRequestTypeFromTemplate(): void
    {
        $this->checkRequestTypeFieldConfiguration(
            form: $this->createAndGetFormWithMultipleRequestTypeQuestions(),
            config: ['value' => RequestTypeField::CONFIG_FROM_TEMPLATE],
            answers: [],
            expected_request_type: Ticket::INCIDENT_TYPE
        );
    }

    public function testSpecificRequestType(): void
    {
        $form = $this->createAndGetFormWithMultipleRequestTypeQuestions();

        // Specific value: DEMAND
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_SPECIFIC_VALUE,
                RequestTypeField::EXTRA_CONFIG_REQUEST_TYPE => Ticket::DEMAND_TYPE,
            ],
            answers: [],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Specific value: INCIDENT
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_SPECIFIC_VALUE,
                RequestTypeField::EXTRA_CONFIG_REQUEST_TYPE => Ticket::INCIDENT_TYPE,
            ],
            answers: [],
            expected_request_type: Ticket::INCIDENT_TYPE
        );
    }

    public function testRequestTypeFromSpecificQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleRequestTypeQuestions();

        // Using answer from first question
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_SPECIFIC_ANSWER,
                RequestTypeField::EXTRA_CONFIG_QUESTION_ID => $this->getQuestionId($form, "Request type 1"),
            ],
            answers: [
                "Request type 1" => Ticket::DEMAND_TYPE,
                "Request type 2" => Ticket::INCIDENT_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Using answer from second question
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_SPECIFIC_ANSWER,
                RequestTypeField::EXTRA_CONFIG_QUESTION_ID => $this->getQuestionId($form, "Request type 2"),
            ],
            answers: [
                "Request type 1" => Ticket::DEMAND_TYPE,
                "Request type 2" => Ticket::INCIDENT_TYPE,
            ],
            expected_request_type: Ticket::INCIDENT_TYPE
        );
    }

    public function testRequestTypeFromLastValidQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleRequestTypeQuestions();

        // With multiple answers submitted
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_LAST_VALID_ANSWER,
            ],
            answers: [
                "Request type 1" => Ticket::INCIDENT_TYPE,
                "Request type 2" => Ticket::DEMAND_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Only first answer was submitted
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_LAST_VALID_ANSWER,
            ],
            answers: [
                "Request type 1" => Ticket::DEMAND_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // Only second answer was submitted
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_LAST_VALID_ANSWER,
            ],
            answers: [
                "Request type 2" => Ticket::DEMAND_TYPE,
            ],
            expected_request_type: Ticket::DEMAND_TYPE
        );

        // No answers, fallback to default value
        $this->checkRequestTypeFieldConfiguration(
            form: $form,
            config: [
                'value' => RequestTypeField::CONFIG_LAST_VALID_ANSWER,
            ],
            answers: [],
            expected_request_type: Ticket::DEMAND_TYPE
        );
    }

    private function checkRequestTypeFieldConfiguration(
        Form $form,
        array $config,
        array $answers,
        int $expected_request_type
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => ['request_type' => $config]],
            ["config"],
        );

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            $formatted_answers[$key] = $answer;
        }

        // Submit form
        $this->login();
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            \Session::getLoginUserID()
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check request type
        $this->assertEquals($expected_request_type, $ticket->fields['type']);
    }

    private function createAndGetFormWithMultipleRequestTypeQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Request type 1", QuestionTypeRequestType::class);
        $builder->addQuestion("Request type 2", QuestionTypeRequestType::class);
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
