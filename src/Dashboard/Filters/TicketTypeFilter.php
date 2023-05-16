<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Dashboard\Filters;

use Session;
use Ticket;
use DBmysql;

class TicketTypeFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return _n("Ticket type", "Ticket types", Session::getPluralNumber());
    }

    public static function getId(): string
    {
        return "tickettype";
    }

    public static function getCriteria(DBmysql $DB, string $table, $value): array
    {
        $criteria = [];

        if ((int) $value > 0 && $DB->fieldExists($table, 'type')) {
            $criteria["WHERE"] = [
                "$table.type" => (int) $value
            ];
        }

        return $criteria;
    }

    public static function getSearchCriteria(DBmysql $DB, string $table, $value): array
    {
        $criteria = [];

        if ((int) $value > 0 && $DB->fieldExists($table, 'type')) {
            $criteria[] = [
                'link'       => 'AND',
                'field'      => self::getSearchOptionID($table, 'type', $table),
                'searchtype' => 'equals',
                'value'      => (int) $value
            ];
        }
        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            'tickettype',
            Ticket::class,
            [
                'condition' => ['id' => -1],
                'toadd'     => Ticket::getTypes()
            ]
        );
    }
}
