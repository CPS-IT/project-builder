<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2022 Elias Häußler <e.haeussler@familie-redlich.de>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace CPSIT\ProjectBuilder\Tests\Twig\Func;

use CPSIT\ProjectBuilder as Src;
use Generator;
use PHPUnit\Framework;

/**
 * DefaultProjectNameFunctionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DefaultProjectNameFunctionTest extends Framework\TestCase
{
    private Src\Twig\Func\DefaultProjectNameFunction $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Twig\Func\DefaultProjectNameFunction();
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('invokeReturnsTrueIfGivenProjectNameIsTheDefaultDataProvider')]
    public function invokeReturnsTrueIfGivenProjectNameIsTheDefault(?string $projectName, bool $expected): void
    {
        $actual = ($this->subject)($projectName);

        self::assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{string|null, bool}>
     */
    public static function invokeReturnsTrueIfGivenProjectNameIsTheDefaultDataProvider(): Generator
    {
        yield 'null' => [null, true];
        yield 'basic' => ['basic', true];
        yield 'something different' => ['foo', false];
    }
}
