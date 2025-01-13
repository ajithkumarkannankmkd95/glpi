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

namespace tests\units\Glpi\Exception;

use Glpi\Log\LogLineFormatter;
use PHPUnit\Framework\TestCase;

class LogLineFormatterTest extends TestCase
{
    public function testBasicExceptionFormat(): void
    {
        $formatter = new LogLineFormatter();

        $normalizedExceptionMessage = $formatter->normalizeValue(new \RuntimeException('NOOP'));

        self::assertIsString($normalizedExceptionMessage);

        $lines = explode("\n", $normalizedExceptionMessage);
        self::assertSame('NOOP', $lines[0]);
        self::assertSame('  Backtrace :', $lines[1]);
        self::assertMatchesRegularExpression(\sprintf('~%s\(\)$~', \str_replace(['::', '\\'], ['->', '\\\\'], __METHOD__)), $lines[2]);
        self::assertMatchesRegularExpression(\sprintf('~%s->%s\(\)$~', \str_replace('\\', '\\\\', TestCase::class), 'runTest'), $lines[3]);
    }
}
