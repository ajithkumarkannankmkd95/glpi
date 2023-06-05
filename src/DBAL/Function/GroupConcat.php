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

namespace Glpi\DBAL\Function;

use Glpi\DBAL\QueryFunction;

/**
 * Class for formatting an GROUP_CONCAT SQL function
 * @interal Not for direct use. Use {@link QueryFunction} instead.
 */
final class GroupConcat implements Formatter
{
    public static function format(array $parameters): string
    {
        [$expression, $separator, $distinct, $order_by] = $parameters;
        $output = 'GROUP_CONCAT(';
        if ($distinct) {
            $output .= 'DISTINCT ';
        }
        $output .= $expression;
        if ($order_by) {
            $output .= ' ORDER BY ' . $order_by;
        }
        if ($separator) {
            $output .= ' SEPARATOR ' . $separator;
        }
        $output .= ')';
        return $output;
    }
}
