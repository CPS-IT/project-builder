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

namespace CPSIT\ProjectBuilder\Resource\Local;

use SebastianFeldmann\Cli;

use function trim;

/**
 * Git.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Git
{
    public function __construct(
        private Cli\Command\Runner $runner,
    ) {
    }

    public function getAuthorName(): ?string
    {
        return $this->readConfig('user.name') ?? $this->readConfig('author.name');
    }

    public function getAuthorEmail(): ?string
    {
        return $this->readConfig('user.email') ?? $this->readConfig('author.email');
    }

    private function readConfig(string $configPath): ?string
    {
        $command = (new Cli\Command\Executable('git'))
            ->addArgument('config')
            ->addOption('--global')
            ->addOption('--default', '')
            ->addOption('--includes')
            ->addOption('--get')
            ->addOption($configPath)
        ;

        return $this->run($command);
    }

    private function run(Cli\Command $command): ?string
    {
        $result = $this->runner->run($command);

        if (!$result->isSuccessful() || [] === $result->getBufferedOutput()) {
            return null;
        }

        $output = $result->getBufferedOutput()[0];

        return '' !== trim($output) ? trim($output) : null;
    }
}
