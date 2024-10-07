<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\System\Requirement;

use GLPITestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class DBmysqlTest extends GLPITestCase
{
    public static function cantCreateMyIsamTableProvider(): iterable
    {
        return [
            ['engine=MyISAM'], // without ending `;`
            ['engine=MyISAM;'], // with ending `;`
            [' Engine =  myisam '], // mixed case
            ['   ENGINE  =    MYISAM  '], // uppercase with lots of spaces
            [" ENGINE = 'MyISAM'"], // surrounded by quotes
            ["ROW_FORMAT=DYNAMIC ENGINE=MyISAM"], // preceded by another option
            ["ENGINE=MyISAM ROW_FORMAT=DYNAMIC"], // followed by another option
        ];
    }

    #[DataProvider('cantCreateMyIsamTableProvider')]
    public function testCantCreateMyIsamTable(string $table_options): void
    {
        /** @var \DBmysql $db */
        global $DB;

        $this->expectException(InvalidArgumentException::class);
        $DB->doQuery(<<<SQL
            CREATE TABLE `glpi_tmp_testCantCreateMyIsamTable` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`)
            ) $table_options
        SQL);
    }
}
