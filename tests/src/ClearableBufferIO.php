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

namespace CPSIT\ProjectBuilder\Tests;

use Composer\IO;
use Symfony\Component\Console;

use function assert;
use function fseek;
use function ftruncate;

/**
 * ClearableBufferIO.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ClearableBufferIO extends IO\BufferIO
{
    /**
     * @var callable
     */
    private $restoreInitialState;

    /**
     * @phpstan-param Console\Output\OutputInterface::VERBOSITY_* $verbosity
     */
    public function __construct(
        string $input = '',
        int $verbosity = Console\Output\OutputInterface::VERBOSITY_NORMAL,
        ?Console\Formatter\OutputFormatterInterface $formatter = null,
    ) {
        parent::__construct($input, $verbosity, $formatter);

        $this->restoreInitialState = function () use ($input, $verbosity, $formatter) {
            $this->input = new Console\Input\StringInput($input);
            $this->input->setInteractive(false);

            $this->clear();
            $this->output->setVerbosity($verbosity);
            $this->output->setDecorated(null !== $formatter && $formatter->isDecorated());
        };
    }

    public function reset(): void
    {
        ($this->restoreInitialState)();
    }

    public function clear(): void
    {
        assert($this->output instanceof Console\Output\StreamOutput);

        ftruncate($this->output->getStream(), 0);
        fseek($this->output->getStream(), 0);
    }

    public function makeInteractive(bool $interactive = true): self
    {
        $this->input->setInteractive($interactive);

        return $this;
    }

    /**
     * @phpstan-param Console\Output\OutputInterface::VERBOSITY_* $level
     */
    public function setVerbosity(int $level): self
    {
        $this->output->setVerbosity($level);

        return $this;
    }
}
