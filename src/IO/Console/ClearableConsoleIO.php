<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2024 Elias Häußler <e.haeussler@familie-redlich.de>
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
use CPSIT\ProjectBuilder\Exception;
use Symfony\Component\Console;

/**
 * ClearableConsoleIO.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ClearableConsoleIO extends IO\ConsoleIO
{
    /**
     * @var Console\Output\ConsoleOutput
     */
    protected $output;

    private ?Console\Output\ConsoleSectionOutput $section = null;

    public function __construct(
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
        Console\Helper\HelperSet $helperSet,
    ) {
        if (!($output instanceof Console\Output\ConsoleOutput)) {
            throw Exception\UnsupportedOutputException::create($output);
        }

        parent::__construct($input, $output, $helperSet);
    }

    public static function from(IO\ConsoleIO $io): self
    {
        return new self($io->input, $io->output, $io->helperSet);
    }

    public function section(): Console\Output\ConsoleSectionOutput
    {
        return $this->section = $this->output->section();
    }

    public function clear(): void
    {
        $this->section?->clear();
    }
}
