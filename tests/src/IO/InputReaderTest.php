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

namespace CPSIT\ProjectBuilder\Tests\IO;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;

/**
 * InputReaderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InputReaderTest extends Tests\ContainerAwareTestCase
{
    private Src\IO\InputReader $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\IO\InputReader(self::$io);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staticValueReturnsUserInput(): void
    {
        self::$io->setUserInputs(['Bob']);

        self::assertSame('Bob', $this->subject->staticValue('What\'s your name?', 'Alice'));
        self::assertStringContainsString('What\'s your name? (optional) [Alice]', self::$io->getOutput());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staticValueReturnsDefaultValue(): void
    {
        self::assertSame('Alice', $this->subject->staticValue('What\'s your name?', 'Alice'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hiddenValueHidesUserInput(): void
    {
        self::$io->setUserInputs(['s3cr3t']);

        self::assertSame('s3cr3t', $this->subject->hiddenValue('What\'s your password?'));

        $output = self::$io->getOutput();

        self::assertStringContainsString('What\'s your password?', $output);
        self::assertStringNotContainsString('s3cr3t', $output);
    }
}
