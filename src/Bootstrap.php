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

use Composer\Script;
use Exception;

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

    private function __construct(
        IO\Messenger $messenger,
        Builder\Config\ConfigReader $configReader,
        Error\ErrorHandler $errorHandler
    ) {
        $this->messenger = $messenger;
        $this->configReader = $configReader;
        $this->errorHandler = $errorHandler;
    }

    public static function fromMessenger(IO\Messenger $messenger): self
    {
        return new self(
            $messenger,
            Builder\Config\ConfigReader::create(),
            new Error\ErrorHandler($messenger)
        );
    }

    /**
     * Create a new project.
     *
     * This is the entrypoint for the "composer create-project" command,
     * see composer.json in repository root.
     */
    public static function createProject(Script\Event $event): void
    {
        $messenger = IO\Messenger::create($event->getIO());
        $exitCode = self::fromMessenger($messenger)->run();

        $event->stopPropagation();

        if ($exitCode > 0) {
            exit($exitCode);
        }
    }

    public function run(): int
    {
        if (!$this->messenger->isInteractive()) {
            $this->messenger->error('This command cannot be run in non-interactive mode.');

            return 1;
        }

        $this->messenger->clearScreen();
        $this->messenger->welcome();

        try {
            $templateIdentifier = $this->messenger->selectTemplate($this->configReader->listTemplates());
            $config = $this->configReader->readConfig($templateIdentifier);
            $container = $this->buildContainer($config);

            $targetDirectory = Helper\FilesystemHelper::getProjectRootPath();
            $generator = $container->get(Builder\Generator\Generator::class);
            $result = $generator->run($targetDirectory);

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

    private function buildContainer(Builder\Config\Config $config): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        $factory = DependencyInjection\ContainerFactory::createFromConfig($config, $this->messenger->isDebug());
        $container = $factory->get();

        $container->set('app.config', $config);
        $container->set('app.messenger', $this->messenger);

        return $container;
    }
}
