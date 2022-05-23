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

namespace CPSIT\ProjectBuilder\Composer;

use Composer\Console;
use Symfony\Component\Console as SymfonyConsole;
use function dirname;

/**
 * ComposerInstaller.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ComposerInstaller
{
    private Console\Application $application;

    public function __construct()
    {
        $this->application = new Console\Application();
        $this->application->setAutoExit(false);
    }

    public function install(string $composerJson, SymfonyConsole\Output\OutputInterface &$output = null): int
    {
        $input = new SymfonyConsole\Input\ArrayInput([
            'command' => 'update',
            '--working-dir' => dirname($composerJson),
            '--no-dev' => true,
            '--prefer-dist' => true,
        ]);

        if (null === $output) {
            $output = new SymfonyConsole\Output\BufferedOutput();
        }

        return $this->application->run($input, $output);
    }
}
