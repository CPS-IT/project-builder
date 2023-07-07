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

namespace CPSIT\ProjectBuilder\IO\Console;

use Composer\IO;

/**
 * TraceableConsoleIO.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TraceableConsoleIO extends IO\ConsoleIO
{
    private bool $silenced = false;
    private bool $outputWritten = false;

    public function write($messages, $newline = true, $verbosity = self::NORMAL): void
    {
        if (!$this->silenced) {
            parent::write($messages, $newline, $verbosity);
        }

        $this->outputWritten = true;
    }

    public function writeError($messages, $newline = true, $verbosity = self::NORMAL): void
    {
        if (!$this->silenced) {
            parent::writeError($messages, $newline, $verbosity);
        }

        $this->outputWritten = true;
    }

    public function writeRaw($messages, $newline = true, $verbosity = self::NORMAL): void
    {
        if (!$this->silenced) {
            parent::writeRaw($messages, $newline, $verbosity);
        }

        $this->outputWritten = true;
    }

    public function silence(): void
    {
        $this->silenced = true;
    }

    public function unsilence(): void
    {
        $this->silenced = false;
    }

    public function isOutputWritten(): bool
    {
        return $this->outputWritten;
    }
}
