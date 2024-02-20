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

namespace Glpi\Form;

use CommonDBChild;
use Override;

/**
 * Section of a given helpdesk form
 */
final class Section extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    /**
     * Lazy loaded array of questions
     * Should always be accessed through getQuestions()
     * @var Question[]|null
     */
    protected ?array $questions = null;

    #[Override]
    public function post_getFromDB()
    {
        // Clear any lazy loaded data
        $this->clearLazyLoadedData();
    }

    /**
     * Get questions of this section
     *
     * @return Question[]
     */
    public function getQuestions(): array
    {
        // Lazy loading
        if ($this->questions === null) {
            $this->questions = [];

            // Read from database
            $questions_data = (new Question())->find(
                [self::getForeignKeyField() => $this->fields['id']],
                'rank ASC',
            );
            foreach ($questions_data as $row) {
                $question = new Question();
                $question->getFromResultSet($row);
                $question->post_getFromDB();
                $this->questions[$row['id']] = $question;
            }
        }

        return $this->questions;
    }

    /**
     * Clear lazy loaded data
     *
     * @return void
     */
    protected function clearLazyLoadedData(): void
    {
        $this->questions = null;
    }
}
