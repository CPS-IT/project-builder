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

use Composer\Factory;
use Composer\Script;
use Composer\XdebugHandler;
use Exception;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;
use function chdir;
use function dirname;
use function getcwd;
use function putenv;
use function sprintf;
use function uniqid;

/**
 * Generator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Bootstrap
{
    private IO\Messenger $messenger;
    private Builder\Config\ConfigReader $configReader;
    private Error\ErrorHandler $errorHandler;
    private Filesystem\Filesystem $filesystem;
    private string $targetDirectory;

    private function __construct(
        IO\Messenger $messenger,
        Builder\Config\ConfigReader $configReader,
        Error\ErrorHandler $errorHandler,
        Filesystem\Filesystem $filesystem,
        string $targetDirectory = null
    ) {
        $this->messenger = $messenger;
        $this->configReader = $configReader;
        $this->errorHandler = $errorHandler;
        $this->filesystem = $filesystem;
        $this->targetDirectory = $targetDirectory ?? Helper\FilesystemHelper::getProjectRootPath();
    }

    public static function create(IO\Messenger $messenger, string $targetDirectory = null): self
    {
        return new self(
            $messenger,
            Builder\Config\ConfigReader::create(),
            new Error\ErrorHandler($messenger),
            new Filesystem\Filesystem(),
            $targetDirectory
        );
    }

    /**
     * Create a new project.
     *
     * This is the entrypoint for the "composer create-project" command,
     * see composer.json in repository root.
     */
    public static function createProject(
        Script\Event $event,
        string $targetDirectory = null,
        bool $exitOnFailure = true
    ): int {
        $messenger = IO\Messenger::create($event->getIO());
        $exitCode = self::create($messenger, $targetDirectory)->run();

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
     * @codeCoverageIgnore
     */
    public static function simulateCreateProject(Script\Event $event): void
    {
        $output = Factory::createOutput();
        $io = new Console\Style\SymfonyStyle(new Console\Input\ArgvInput(), $output);

        $filesystem = new Filesystem\Filesystem();
        $rootPath = Helper\FilesystemHelper::getProjectRootPath();
        $targetDirectory = Filesystem\Path::join($rootPath, '.build', uniqid('simulate_'));

        // Override current root path
        putenv('PROJECT_BUILDER_ROOT_PATH='.$targetDirectory);

        // Remove old simulations
        $oldSimulations = Finder\Finder::create()
            ->directories()
            ->in(dirname($targetDirectory))
            ->name('simulate_*')
            ->depth('== 0')
        ;
        $filesystem->remove($oldSimulations);

        // Mirror project files to the simulation directory and install
        // Composer dependencies. This mimics the behavior of
        // "composer create-project" installing the repository into the
        // specified directory.
        $projectFiles = Finder\Finder::create()
            ->in($rootPath)
            ->notPath('.build')
            ->notPath('vendor')
            ->notName('composer.lock')
        ;
        $filesystem->mirror(dirname(__DIR__), $targetDirectory, $projectFiles);
        $composer = new Resource\Local\Composer($filesystem);

        // Disable Xdebug
        XdebugHandler\Process::setEnv('COMPOSER_ALLOW_XDEBUG');

        // Install project
        $exitCode = $composer->install(
            Filesystem\Path::join($targetDirectory, 'composer.json'),
            true,
            $output
        );

        if ($exitCode > 0) {
            $io->error(
                sprintf('Unable to install project dependencies. Exit code: %d', $exitCode),
            );

            exit($exitCode);
        }

        // Switch to simulation directory
        $initialWorkingDirectory = false !== getcwd() ? getcwd() : Helper\FilesystemHelper::getProjectRootPath();
        chdir($targetDirectory);

        // Run project creation
        $exitCode = self::createProject($event, $targetDirectory, false);

        // Go back to initial working directory
        chdir($initialWorkingDirectory);

        $message = sprintf('Simulation finished with exit code: %d', $exitCode);
        if ($exitCode > 0) {
            $io->error($message);
        } else {
            $io->success($message);
        }
        $io->writeln(
            sprintf('Target directory: <comment>%s</comment>', $targetDirectory)
        );

        exit($exitCode);
    }

    public function run(): int
    {
        if (!$this->messenger->isInteractive()) {
            $this->messenger->error('This command cannot be run in non-interactive mode.');

            return 1;
        }

        $mirroredSourcePath = $this->mirrorSourceFiles();
        $loader = Resource\Local\Composer::createClassLoader($mirroredSourcePath);
        $loader->register(true);

        $this->messenger->clearScreen();
        $this->messenger->welcome();

        try {
            $templateIdentifier = $this->messenger->selectTemplate($this->configReader->listTemplates());
            $config = $this->configReader->readConfig($templateIdentifier);
            $container = $this->buildContainer($config);

            $generator = $container->get(Builder\Generator\Generator::class);
            $result = $generator->run($this->targetDirectory);

            $this->messenger->decorateResult($result);

            if (!$result->isMirrored()) {
                return 1;
            }

            $generator->cleanUp($result);
        } catch (Exception $exception) {
            $this->errorHandler->handleException($exception);

            return 1;
        }

        return 0;
    }

    private function mirrorSourceFiles(): string
    {
        $sourceDirectory = Filesystem\Path::join($this->targetDirectory, Paths::PROJECT_SOURCES);
        $targetDirectory = Filesystem\Path::join($this->targetDirectory, '.build', Paths::PROJECT_SOURCES);

        $this->filesystem->mirror($sourceDirectory, $targetDirectory);

        return $targetDirectory;
    }

    private function buildContainer(Builder\Config\Config $config): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        $factory = DependencyInjection\ContainerFactory::createFromConfig($config);
        $container = $factory->get();

        $container->set('app.config', $config);
        $container->set('app.messenger', $this->messenger);

        return $container;
    }
}
