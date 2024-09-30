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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Override;

final class AssociatedItemsFieldConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const QUESTION_IDS = 'question_ids';
    public const ASSOCIATED_ITEMS = 'associated_items';

    public function __construct(
        private AssociatedItemsFieldStrategy $strategy,
        private ?array $specific_question_ids = null,
        private ?array $specific_associated_items = null,
    ) {
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = AssociatedItemsFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");
        if ($strategy === null) {
            $strategy = AssociatedItemsFieldStrategy::ALL_VALID_ANSWERS;
        }

        return new self(
            strategy: $strategy,
            specific_question_ids: $data[self::QUESTION_IDS] ?? null,
            specific_associated_items: $data[self::ASSOCIATED_ITEMS] ?? null,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy->value,
            self::QUESTION_IDS => $this->specific_question_ids,
            self::ASSOCIATED_ITEMS => $this->specific_associated_items,
        ];
    }

    public function getStrategy(): AssociatedItemsFieldStrategy
    {
        return $this->strategy;
    }

    public function getSpecificQuestionIds(): ?array
    {
        return $this->specific_question_ids;
    }

    public function getSpecificAssociatedItems(): ?array
    {
        return $this->specific_associated_items;
    }
}
