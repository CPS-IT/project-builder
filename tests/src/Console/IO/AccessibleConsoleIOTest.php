<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Tests\Console\IO;

use Composer\IO;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Symfony\Component\Console;

/**
 * AccessibleConsoleIOTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AccessibleConsoleIOTest extends Tests\ContainerAwareTestCase
{
    private Src\Console\IO\AccessibleConsoleIO $subject;

    protected function setUp(): void
    {
        $this->subject = Src\Console\IO\AccessibleConsoleIO::fromIO(self::$io);
    }

    /**
     * @test
     */
    public function fromIOConstructsInputAndOutputIfIOIsNotConsoleIO(): void
    {
        $actual = Src\Console\IO\AccessibleConsoleIO::fromIO(new IO\NullIO());

        self::assertInstanceOf(Console\Input\ArgvInput::class, $actual->getInput());
        self::assertInstanceOf(Console\Output\ConsoleOutput::class, $actual->getOutput());
    }

    /**
     * @test
     */
    public function getInputReturnsInput(): void
    {
        self::$io->makeInteractive();

        $input = $this->subject->getInput();

        self::assertTrue($input->isInteractive());
        self::assertTrue(self::$io->isInteractive());

        $input->setInteractive(false);

        self::assertFalse($input->isInteractive());
        self::assertFalse(self::$io->isInteractive());
    }

    /**
     * @test
     */
    public function getOutputReturnsOutput(): void
    {
        $output = $this->subject->getOutput();
        $output->write('hello world');

        self::assertSame('hello world', self::$io->getOutput());
    }
}
