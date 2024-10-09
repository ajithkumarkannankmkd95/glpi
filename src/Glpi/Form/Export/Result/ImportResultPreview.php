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

namespace Glpi\Form\Export\Result;

final class ImportResultPreview
{
    /** @var string[] $valid_forms */
    private array $valid_forms = [];

    /** @var string[] $invalid_forms */
    private array $invalid_forms = [];

    /** @var string[] $fixed_forms */
    private array $fixed_forms = [];

    public function addValidForm(string $form_name): void
    {
        $this->valid_forms[] = $form_name;
    }

    /** @return string[] */
    public function getValidForms(): array
    {
        return $this->valid_forms;
    }

    public function addInvalidForm(string $form_name): void
    {
        $this->invalid_forms[] = $form_name;
    }

    /** @return string[] */
    public function getInvalidForms(): array
    {
        return $this->invalid_forms;
    }

    public function addFixedForm(string $form_name): void
    {
        $this->fixed_forms[] = $form_name;
    }

    /** @return string[] */
    public function getFixedForms(): array
    {
        return $this->fixed_forms;
    }
}
