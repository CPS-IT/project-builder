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

namespace CPSIT\ProjectBuilder;

use Composer\InstalledVersions;
use Composer\Script;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\Filesystem;

/**
 * Bootstrap.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @codeCoverageIgnore
 */
final class Bootstrap
{
    /**
     * Create a new project.
     *
     * This is the entrypoint for the "composer create-project" command,
     * see composer.json in repository root.
     */
    public static function createProject(
        Script\Event $event,
        string $targetDirectory = null,
        bool $exitOnFailure = true,
    ): int {
        $targetDirectory ??= Helper\FilesystemHelper::getWorkingDirectory();

        // Early return if current environment is unsupported
        if (self::runsOnAnUnsupportedEnvironment()) {
            throw Exception\UnsupportedEnvironmentException::forOutdatedComposerInstallation();
        }

        self::prepareEnvironment($targetDirectory);

        // Initialize IO components
        $io = Console\IO\AccessibleConsoleIO::fromIO($event->getIO());
        $messenger = IO\Messenger::create($io);
        $input = new SymfonyConsole\Input\ArrayInput([
            'target-directory' => $targetDirectory,
            '--force' => true,
        ]);
        $input->setInteractive($io->isInteractive());

        // Run project creation
        $command = Console\Command\CreateProjectCommand::create($messenger);
        $exitCode = $command->run($input, $io->getOutput());

        $event->stopPropagation();

        if ($exitCode > 0 && $exitOnFailure) {
            exit($exitCode);
        }

        return $exitCode;
    }

    /**
     * Simulate behavior of {@see Bootstrap::createProject()}.
     *
     * This is the entrypoint to test the original behavior of
     * "composer create-project" command. It is not meant to be used
     * anywhere else than in debugging test cases.
     *
     * @internal
     *
     * @codeCoverageIgnore
     */
    public static function simulateCreateProject(Script\Event $event): void
    {
        $simulation = Console\Simulation::create();
        $targetDirectory = $simulation->prepare();

        $exitCode = $simulation->run(
            static fn (): int => self::createProject($event, $targetDirectory, false),
        );

        exit($exitCode);
    }

    private static function prepareEnvironment(string $targetDirectory): void
    {
        // Mirror source files to build directory
        $filesystem = new Filesystem\Filesystem();
        $filesystem->mirror(
            Filesystem\Path::join($targetDirectory, Paths::PROJECT_SOURCES),
            Filesystem\Path::join($targetDirectory, '.build', Paths::PROJECT_SOURCES),
        );

        // Register modified class loader
        $loader = Resource\Local\Composer::createClassLoader();
        $loader->register(true);
    }

    private static function runsOnAnUnsupportedEnvironment(): bool
    {
        /* @phpstan-ignore-next-line */
        return !method_exists(InstalledVersions::class, 'getInstallPath');
    }
}
