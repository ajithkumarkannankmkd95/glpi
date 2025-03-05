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

namespace tests\units\Glpi\Form\Condition;

use DbTestCase;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\Engine;
use Glpi\Form\Condition\EngineInput;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Condition\Type;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDateTimeExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class EngineTest extends DbTestCase
{
    use FormTesterTrait;

    public static function conditionsOnQuestions(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->addQuestion("Question 3", QuestionTypeShortText::class);
        $form->addQuestion("Question 4", QuestionTypeShortText::class);
        $form->setQuestionVisibility(
            "Question 2",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "correct value",
                ]
            ]
        );
        $form->setQuestionVisibility(
            "Question 3",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 4",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "glpi",
                ]
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => false,
                    'Question 3' => true,
                    'Question 4' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "correct value",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                    'Question 3' => true,
                    'Question 4' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "glpi",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => false,
                    'Question 3' => false,
                    'Question 4' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "correct value",
                    'Question 2' => "",
                    'Question 3' => "",
                    'Question 4' => "glpi",
                ],
            ],
            'expected_output' => [
                'questions' => [
                    'Question 1' => true,
                    'Question 2' => true,
                    'Question 3' => false,
                    'Question 4' => true,
                ],
            ],
        ];
    }

    public static function conditionsOnComments(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->addComment("Comment 1");
        $form->addComment("Comment 2");
        $form->setCommentVisibility(
            "Comment 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "glpi is incredible",
                ]
            ]
        );
        $form->setCommentVisibility(
            "Comment 2",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 2",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "of course",
                ]
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => false,
                    'Comment 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "glpi is incredible",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => true,
                    'Comment 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "of course",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => false,
                    'Comment 2' => false,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "glpi is incredible",
                    'Question 2' => "of course",
                ],
            ],
            'expected_output' => [
                'comments' => [
                    'Comment 1' => true,
                    'Comment 2' => false,
                ],
            ],
        ];
    }

    public static function conditionsOnSections(): iterable
    {
        $form = new FormBuilder();
        $form->addQuestion("Question 1", QuestionTypeShortText::class);
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->addSection("Test section 1");
        $form->addComment("Comment 1");
        $form->addSection("Test section 2");
        $form->addComment("Comment 2");
        $form->setSectionVisibility(
            "Test section 1",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 1",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer for question 1",
                ]
            ]
        );
        $form->setSectionVisibility(
            "Test section 2",
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question 2",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer for question 2",
                ]
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => false,
                    'Test section 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "answer for question 1",
                    'Question 2' => "",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => true,
                    'Test section 2' => true,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "",
                    'Question 2' => "answer for question 2",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => false,
                    'Test section 2' => false,
                ],
            ],
        ];
        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question 1' => "answer for question 1",
                    'Question 2' => "answer for question 2",
                ],
            ],
            'expected_output' => [
                'sections' => [
                    'Test section 1' => true,
                    'Test section 2' => false,
                ],
            ],
        ];
    }

    public static function firstSectionShouldAlwaysBeVisible(): iterable
    {
        $form = new FormBuilder();
        $form->addSection("First section");
        $form->addQuestion("Question used as condition", QuestionTypeShortText::class);
        $form->addSection("Second section");
        $form->addQuestion("Another question", QuestionTypeShortText::class);
        $form->setSectionVisibility(
            "First section",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question used as condition",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "expected answer",
                ]
            ]
        );
        $form->setSectionVisibility(
            "Second section",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Question used as condition",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "expected answer",
                ]
            ]
        );

        yield [
            'form' => $form,
            'input' => [
                'answers' => [
                    'Question used as condition' => "unexpected answer",
                    'Another question' => "doesn't matter",
                ],
            ],
            'expected_output' => [
                // Despite both sections have the same condition, the first one is visible
                'sections' => [
                    'First section' => true,
                    'Second section' => false,
                ],
            ],
        ];
    }

    #[DataProvider('conditionsOnQuestions')]
    #[DataProvider('conditionsOnComments')]
    #[DataProvider('conditionsOnSections')]
    #[DataProvider('firstSectionShouldAlwaysBeVisible')]
    public function testComputation(
        FormBuilder $form,
        array $input,
        array $expected_output,
    ): void {
        // Arrange: create the form and build the correct input
        $form = $this->createForm($form);
        $input = $this->mapInput($form, $input);

        // Act: execute visibility engine
        $engine = new Engine($form, $input);
        $output = $engine->computeVisibility();

        // Assert: validate output
        foreach (($expected_output['questions'] ?? []) as $name => $expected_visibility) {
            $id = $this->getQuestionId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isQuestionVisible($id),
                "Question '$name' does not have the expected visibility.",
            );
        }
        foreach (($expected_output['comments'] ?? []) as $name => $expected_visibility) {
            $id = $this->getCommentId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isCommentVisible($id),
                "Comment '$name' does not have the expected visibility.",
            );
        }
        foreach (($expected_output['sections'] ?? []) as $name => $expected_visibility) {
            $id = $this->getSectionId($form, $name);
            $this->assertEquals(
                $expected_visibility,
                $output->isSectionVisible($id),
                "Section '$name' does not have the expected visibility.",
            );
        }
    }

    public static function conditionsOnStringValues(): iterable
    {
        foreach ([QuestionTypeShortText::class, QuestionTypeEmail::class] as $type) {
            // Test string answers with the EQUALS operator
            yield "Equals check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => false,
            ];
            yield "Equals check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => false,
            ];
            yield "Equals check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => false,
            ];
            yield "Equals check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => true,
            ];
            yield "Equals check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => true,
            ];

            // Test string answers with the NOT_EQUALS operator
            yield "Not equals check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => true,
            ];
            yield "Not equals check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => true,
            ];
            yield "Not equals check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => true,
            ];
            yield "Not equals check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => false,
            ];
            yield "Not equals check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_EQUALS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => false,
            ];

            // Test string answers with the CONTAINS operator
            yield "Contains check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => false,
            ];
            yield "Contains check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => true,
            ];
            yield "Contains check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => true,
            ];
            yield "Contains check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => true,
            ];
            yield "Contains check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => true,
            ];

            // Test string answers with the NOT_CONTAINS operator
            yield "Not contains check - case 1 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "unexpected answer",
                'expected_result'    => true,
            ];
            yield "Not contains check - case 2 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact",
                'expected_result'    => false,
            ];
            yield "Not contains check - case 3 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "answer",
                'expected_result'    => false,
            ];
            yield "Not contains check - case 4 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "Exact answer",
                'expected_result'    => false,
            ];
            yield "Not contains check - case 5 for $type" => [
                'question_type'      => $type,
                'condition_operator' => ValueOperator::NOT_CONTAINS,
                'condition_value'    => "Exact answer",
                'submitted_answer'   => "exact ANSWER",
                'expected_result'    => false,
            ];
        }
    }

    public static function conditionsOnNumberValues(): iterable
    {
        $type = QuestionTypeNumber::class;

        // Test number answers with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => false,
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => false,
        ];
        yield "Equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => true,
        ];
        yield "Equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => false,
        ];
        yield "Equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => true,
        ];

        // Test number answers with the NOT EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => false,
        ];
        yield "Not equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => true,
        ];
        yield "Not equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => false,
        ];

        // Test number answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => true,
        ];
        yield "Greater than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => true,
        ];
        yield "Greater than check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => false,
        ];
        yield "Greater than check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => false,
        ];

        // Test number answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => false,
        ];
        yield "Greater than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => true,
        ];
        yield "Greater than or equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => false,
        ];
        yield "Greater than or equals check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => true,
        ];

        // Test number answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => true,
        ];
        yield "Less than check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => false,
        ];
        yield "Less than check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => false,
        ];
        yield "Less than check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => false,
        ];
        yield "Less than check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => true,
        ];
        yield "Less than check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => false,
        ];

        // Test number answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 11,
            'expected_result'    => false,
        ];
        yield "Less than or equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.00001,
            'expected_result'    => false,
        ];
        yield "Less than or equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 9.9999,
            'expected_result'    => true,
        ];
        yield "Less than or equals check - case 6 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'    => 10,
            'submitted_answer'   => 10.0,
            'expected_result'    => true,
        ];
    }

    public static function conditionsOnRichTextValues(): iterable
    {
        $type = QuestionTypeLongText::class;

        // Test rich text answers with the EQUALS operator
        yield "Equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => false,
        ];
        yield "Equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => false,
        ];
        yield "Equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => false,
        ];
        yield "Equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => true,
        ];
        yield "Equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => true,
        ];

        // Test rich text answers with the NOT_EQUALS operator
        yield "Not equals check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => true,
        ];
        yield "Not equals check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => true,
        ];
        yield "Not equals check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => true,
        ];
        yield "Not equals check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => false,
        ];
        yield "Not equals check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_EQUALS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => false,
        ];

        // Test rich text answers with the CONTAINS operator
        yield "Contains check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => false,
        ];
        yield "Contains check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => true,
        ];
        yield "Contains check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => true,
        ];
        yield "Contains check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => true,
        ];
        yield "Contains check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => true,
        ];

        // Test rich text answers with the NOT_CONTAINS operator
        yield "Not contains check - case 1 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>unexpected answer</p>",
            'expected_result'    => true,
        ];
        yield "Not contains check - case 2 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact</p>",
            'expected_result'    => false,
        ];
        yield "Not contains check - case 3 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>answer</p>",
            'expected_result'    => false,
        ];
        yield "Not contains check - case 4 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>Exact answer</p>",
            'expected_result'    => false,
        ];
        yield "Not contains check - case 5 for $type" => [
            'question_type'      => $type,
            'condition_operator' => ValueOperator::NOT_CONTAINS,
            'condition_value'    => "Exact answer",
            'submitted_answer'   => "<p>exact ANSWER</p>",
            'expected_result'    => false,
        ];
    }

    public static function conditionsOnTimeValues(): iterable
    {
        $type = QuestionTypeDateTime::class;
        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_date_enabled: false,
            is_time_enabled: true,
        );

        // Test time answers with the EQUALS operator
        yield "Equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test time answers with the NOT_EQUALS operator
        yield "Not equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test time answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test time answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than or equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than or equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test time answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Less than check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Less than check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test time answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Less than or equals check - case 2 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Less than or equals check - case 3 for $type (with time)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "15:30",
            'submitted_answer'    => "15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
    }

    public static function conditionsOnDateValues(): iterable
    {
        $type = QuestionTypeDateTime::class;
        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_date_enabled: true,
            is_time_enabled: false,
        );

        // Test date answers with the EQUALS operator
        yield "Equals check - case 1 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-28",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 2 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "2024-02-28",
            'submitted_answer'    => "2024-02-26",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 3 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-27",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test date answers with the NOT_EQUALS operator
        yield "Not equals check - case 1 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-28",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 2 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "2024-02-28",
            'submitted_answer'    => "2024-02-26",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 3 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-27",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test date answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-28",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than check - case 2 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "2024-02-28",
            'submitted_answer'    => "2024-02-26",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than check - case 3 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-27",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test date answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-28",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than or equals check - case 2 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28",
            'submitted_answer'    => "2024-02-26",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than or equals check - case 3 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-27",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test date answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-28",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Less than check - case 2 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "2024-02-28",
            'submitted_answer'    => "2024-02-26",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Less than check - case 3 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-27",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test date answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-28",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Less than or equals check - case 2 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28",
            'submitted_answer'    => "2024-02-26",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Less than or equals check - case 3 for $type (with date)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-27",
            'submitted_answer'    => "2024-02-27",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
    }

    public static function conditionsOnDateAndTimeValues(): iterable
    {
        $type = QuestionTypeDateTime::class;
        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_date_enabled: true,
            is_time_enabled: true,
        );

        // Test datetime answers with the EQUALS operator
        yield "Equals check - case 1 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 2 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Equals check - case 3 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test datetime answers with the NOT_EQUALS operator
        yield "Not equals check - case 1 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 2 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Not equals check - case 3 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::NOT_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test datetime answers with the GREATER_THAN operator
        yield "Greater than check - case 1 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than check - case 2 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than check - case 3 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test datetime answers with the GREATER_THAN_OR_EQUALS operator
        yield "Greater than or equals check - case 1 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:31",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than or equals check - case 2 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:29",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Greater than or equals check - case 3 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::GREATER_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];

        // Test datetime answers with the LESS_THAN operator
        yield "Less than check - case 1 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Less than check - case 2 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Less than check - case 3 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:30",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];

        // Test datetime answers with the LESS_THAN_OR_EQUALS operator
        yield "Less than or equals check - case 1 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:31",
            'expected_result'     => false,
            'question_extra_data' => $extra_data
        ];
        yield "Less than or equals check - case 2 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:29",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
        yield "Less than or equals check - case 3 for $type (with datetime)" => [
            'question_type'       => $type,
            'condition_operator'  => ValueOperator::LESS_THAN_OR_EQUALS,
            'condition_value'     => "2024-02-28 15:30",
            'submitted_answer'    => "2024-02-28 15:30",
            'expected_result'     => true,
            'question_extra_data' => $extra_data
        ];
    }

    /**
     * Similar to `testComputation` but will always use the same simplified form
     * to reduce boilerplate and focus and what is really being tested.
     */
    #[DataProvider('conditionsOnStringValues')]
    #[DataProvider('conditionsOnNumberValues')]
    #[DataProvider('conditionsOnRichTextValues')]
    #[DataProvider('conditionsOnTimeValues')]
    #[DataProvider('conditionsOnDateValues')]
    #[DataProvider('conditionsOnDateAndTimeValues')]
    public function testSingleComputation(
        string $question_type,
        ValueOperator $condition_operator,
        mixed $condition_value,
        mixed $submitted_answer,
        bool $expected_result,
        ?JsonFieldInterface $question_extra_data = null,
    ): void {
        // Arrange: create the given form and build the correct input
        $form = new FormBuilder();
        $form->addQuestion(
            name: "My condition",
            type: $question_type,
            extra_data: $question_extra_data ? json_encode($question_extra_data) : '',
        );
        $form->addQuestion("Test subject", QuestionTypeShortText::class);
        $form->setQuestionVisibility("Test subject", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "My condition",
                'item_type'      => Type::QUESTION,
                'value_operator' => $condition_operator,
                'value'          => $condition_value,
            ]
        ]);

        $form = $this->createForm($form);
        $input = $this->mapInput($form, [
            'answers' => ['My condition' => $submitted_answer],
        ]);

        // Act: execute visibility engine
        $engine = new Engine($form, $input);
        $output = $engine->computeVisibility();

        // Assert: validate output
        $id = $this->getQuestionId($form, "Test subject");
        $this->assertEquals(
            $expected_result,
            $output->isQuestionVisible($id),
        );
    }

    public static function conditionnalCreationProvider(): iterable
    {
        yield 'answer 1' => [
            'answer' => 'answer 1',
            'expected' => [
                'Ticket always created' => true,
                'Ticket created if answer 1' => true,
                'Ticket created if answer 2' => false,
                'Ticket created unless answer 1' => false,
                'Ticket created unless answer 2' => true,
            ]
        ];

        yield 'answer 2' => [
            'answer' => 'answer 2',
            'expected' => [
                'Ticket always created' => true,
                'Ticket created if answer 1' => false,
                'Ticket created if answer 2' => true,
                'Ticket created unless answer 1' => true,
                'Ticket created unless answer 2' => false,
            ]
        ];

        yield 'another answer' => [
            'answer' => 'another answer',
            'expected' => [
                'Ticket always created' => true,
                'Ticket created if answer 1' => false,
                'Ticket created if answer 2' => false,
                'Ticket created unless answer 1' => true,
                'Ticket created unless answer 2' => true,
            ]
        ];
    }

    #[DataProvider('conditionnalCreationProvider')]
    public function testConditionnalCreation(
        string $answer,
        array $expected
    ): void {
        // Arrange: create a complex form with multiples conditionnal destinations
        $form = new FormBuilder();
        $form->addQuestion("My answer", QuestionTypeShortText::class);
        $form->addDestination(FormDestinationTicket::class, "Ticket always created");
        $form->setDestinationCondition(
            "Ticket always created",
            CreationStrategy::ALWAYS_CREATED,
            [],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created if answer 1"
        );
        $form->setDestinationCondition(
            "Ticket created if answer 1",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 1",
                ],
            ],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created if answer 2"
        );
        $form->setDestinationCondition(
            "Ticket created if answer 2",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 2",
                ],
            ],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created unless answer 1"
        );
        $form->setDestinationCondition(
            "Ticket created unless answer 1",
            CreationStrategy::CREATED_UNLESS,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 1",
                ],
            ],
        );
        $form->addDestination(
            FormDestinationTicket::class,
            "Ticket created unless answer 2"
        );
        $form->setDestinationCondition(
            "Ticket created unless answer 2",
            CreationStrategy::CREATED_UNLESS,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My answer",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "answer 2",
                ],
            ],
        );
        $form = $this->createForm($form);

        // Act: compute the conditions
        $input = $this->mapInput($form, ['answers' => ['My answer' => $answer]]);
        $engine = new Engine($form, $input);
        $output = $engine->computeItemsThatMustBeCreated();

        // Assert: validate the created tickets
        foreach ($expected as $name => $must_be_created) {
            $destination = FormDestination::getById(
                $this->getDestinationId($form, $name)
            );
            $this->assertEquals(
                $must_be_created,
                $output->itemMustBeCreated($destination)
            );
        }
    }

    /**
     * Transform a simplified raw input that uses questions names by a real
     * EngineInput object with the correct ids.
     */
    private function mapInput(Form $form, array $raw_input): EngineInput
    {
        $answers = [];
        foreach ($raw_input['answers'] as $question_name => $answer) {
            $question_id = $this->getQuestionId($form, $question_name);
            $answers[$question_id] = $answer;
        }

        return new EngineInput($answers);
    }
}
