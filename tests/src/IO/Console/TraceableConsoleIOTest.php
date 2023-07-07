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

namespace CPSIT\ProjectBuilder\Tests\IO\Console;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console;

/**
 * TraceableConsoleIOTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TraceableConsoleIOTest extends Framework\TestCase
{
    private Tests\BufferedConsoleOutput $output;
    private Src\IO\Console\TraceableConsoleIO $subject;

    protected function setUp(): void
    {
        $this->output = new Tests\BufferedConsoleOutput();
        $this->subject = new Src\IO\Console\TraceableConsoleIO(
            new Console\Input\StringInput(''),
            $this->output,
            new Console\Helper\HelperSet(),
        );
    }

    #[Framework\Attributes\Test]
    public function silenceSilencesOutput(): void
    {
        $this->subject->silence();

        $this->subject->write('foo', false);

        self::assertSame('', $this->output->fetch());
        self::assertTrue($this->subject->isOutputWritten());
    }

    #[Framework\Attributes\Test]
    public function unsilenceUnsilencesOutput(): void
    {
        $this->subject->silence();

        $this->subject->write('foo', false);

        $this->subject->unsilence();

        $this->subject->write('baz', false);

        self::assertSame('baz', $this->output->fetch());
        self::assertTrue($this->subject->isOutputWritten());
    }

    #[Framework\Attributes\Test]
    public function isOutputWrittenReturnsTrueIfMessagesWereWritten(): void
    {
        self::assertFalse($this->subject->isOutputWritten());

        $this->subject->write('foo');

        self::assertTrue($this->subject->isOutputWritten());
    }

    #[Framework\Attributes\Test]
    public function isOutputWrittenReturnsTrueIfErrorMessagesWereWritten(): void
    {
        self::assertFalse($this->subject->isOutputWritten());

        $this->subject->writeError('foo');

        self::assertTrue($this->subject->isOutputWritten());
    }

    #[Framework\Attributes\Test]
    public function isOutputWrittenReturnsTrueIfRawMessagesWereWritten(): void
    {
        self::assertFalse($this->subject->isOutputWritten());

        $this->subject->writeRaw('foo');

        self::assertTrue($this->subject->isOutputWritten());
    }
}
