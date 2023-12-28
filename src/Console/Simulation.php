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

namespace CPSIT\ProjectBuilder\Console;

use Composer\Factory;
use Composer\XdebugHandler;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

/**
 * Simulation.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @codeCoverageIgnore
 */
final class Simulation
{
    private readonly Console\Style\SymfonyStyle $io;

    private function __construct(
        private readonly Filesystem\Filesystem $filesystem,
        private readonly Resource\Local\Composer $composer,
        private Console\Output\OutputInterface $output,
        private readonly string $rootPath,
        private readonly string $targetDirectory,
    ) {
        $this->io = new Console\Style\SymfonyStyle(new Console\Input\ArgvInput(), $this->output);
    }

    public static function create(): self
    {
        $filesystem = new Filesystem\Filesystem();
        $rootPath = Helper\FilesystemHelper::getProjectRootPath();

        return new self(
            $filesystem,
            new Resource\Local\Composer($filesystem),
            Factory::createOutput(),
            $rootPath,
            Filesystem\Path::join($rootPath, '.build', uniqid('simulate_')),
        );
    }

    public function prepare(): string
    {
        // Override current root path
        putenv('PROJECT_BUILDER_ROOT_PATH=' . $this->targetDirectory);

        // Remove old simulations
        $this->removeLegacySimulations();

        // Mirror project files to the simulation directory and install
        // Composer dependencies. This mimics the behavior of
        // "composer create-project" installing the repository into the
        // specified directory.
        $this->mirrorProjectFiles();

        // Disable Xdebug
        XdebugHandler\Process::setEnv('COMPOSER_ALLOW_XDEBUG');

        // Install project
        $exitCode = $this->composer->install(
            Filesystem\Path::join($this->targetDirectory, 'composer.json'),
            true,
            $this->output,
        );

        // Early exit if composer install fails
        if ($exitCode > 0) {
            $this->io->error(
                sprintf('Unable to install project dependencies. Exit code: %d', $exitCode),
            );

            exit($exitCode);
        }

        return $this->targetDirectory;
    }

    /**
     * @param callable(): int $applicationCode
     */
    public function run(callable $applicationCode): int
    {
        // Switch to simulation directory
        $initialWorkingDirectory = false !== getcwd() ? getcwd() : Helper\FilesystemHelper::getProjectRootPath();
        chdir($this->targetDirectory);

        // Run project creation
        $exitCode = $applicationCode();

        // Go back to initial working directory
        chdir($initialWorkingDirectory);

        $message = sprintf('Simulation finished with exit code: %d', $exitCode);
        if ($exitCode > 0) {
            $this->io->error($message);
        } else {
            $this->io->success($message);
        }
        $this->io->writeln(
            sprintf('Target directory: <comment>%s</comment>', $this->targetDirectory),
        );

        return $exitCode;
    }

    private function removeLegacySimulations(): void
    {
        $oldSimulations = Finder\Finder::create()
            ->directories()
            ->in(dirname($this->targetDirectory))
            ->name('simulate_*')
            ->depth('== 0')
        ;

        $this->filesystem->remove($oldSimulations);
    }

    private function mirrorProjectFiles(): void
    {
        $projectFiles = Finder\Finder::create()
            ->in($this->rootPath)
            ->notPath('.build')
            ->notPath('vendor')
            ->notName('composer.lock')
        ;

        $this->filesystem->mirror(dirname(__DIR__, 2), $this->targetDirectory, $projectFiles);
    }
}
