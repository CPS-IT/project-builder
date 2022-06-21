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
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework\TestCase;

/**
 * DefaultAuthorNameFunctionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DefaultAuthorNameFunctionTest extends TestCase
{
    private Tests\Fixtures\DummyRunner $runner;
    private Src\Twig\Func\DefaultAuthorNameFunction $subject;

    protected function setUp(): void
    {
        $this->runner = new Tests\Fixtures\DummyRunner();
        $this->subject = new Src\Twig\Func\DefaultAuthorNameFunction(new Src\Resource\Local\Git($this->runner));
    }

    /**
     * @test
     */
    public function invokeReturnsAuthorNameFromGit(): void
    {
        $this->runner->expectedResults[] = ['Foo Bar'];

        $actual = ($this->subject)();

        self::assertSame('Foo Bar', $actual);
    }
}
