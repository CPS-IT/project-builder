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

namespace CPSIT\ProjectBuilder\Tests;

use RuntimeException;
use Symfony\Component\Console;

use function fopen;
use function fseek;
use function is_string;
use function stream_get_contents;

/**
 * BufferedConsoleOutput.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class BufferedConsoleOutput extends Console\Output\StreamOutput implements Console\Output\ConsoleOutputInterface
{
    /**
     * @var resource
     */
    private $stream;
    private Console\Output\OutputInterface $stderr;

    public function __construct()
    {
        $this->stream = $this->createStream();
        $this->stderr = new Console\Output\StreamOutput($this->createStream());

        parent::__construct($this->stream, decorated: false);
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function fetch(): string
    {
        fseek($this->stream, 0);

        $output = stream_get_contents($this->stream);

        if (!is_string($output)) {
            throw new RuntimeException('The output stream cannot be processed.', 1687607309);
        }

        return $output;
    }

    public function getErrorOutput(): Console\Output\OutputInterface
    {
        return $this->stderr;
    }

    public function setErrorOutput(Console\Output\OutputInterface $error): void
    {
        $this->stderr = $error;
    }

    public function section(): Console\Output\ConsoleSectionOutput
    {
        $sections = [];

        return new Console\Output\ConsoleSectionOutput(
            $this->stream,
            $sections,
            $this->getVerbosity(),
            $this->isDecorated(),
            $this->getFormatter(),
        );
    }

    /**
     * @return resource
     */
    private function createStream()
    {
        $stream = fopen('php://temp', 'w+');

        if (false === $stream) {
            throw new RuntimeException('No output stream is available.', 1687607307);
        }

        return $stream;
    }
}
