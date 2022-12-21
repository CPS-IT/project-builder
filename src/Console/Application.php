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

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\DependencyInjection;
use CPSIT\ProjectBuilder\Error;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Paths;
use CPSIT\ProjectBuilder\Resource;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Filesystem;
use Throwable;

/**
 * Application.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class Application
{
    private const SUCCESSFUL = 0;
    private const FAILED = 1;
    private const ABORTED = 2;

    /**
     * @var non-empty-list<Template\Provider\ProviderInterface>
     */
    private array $templateProviders;

    /**
     * @param list<Template\Provider\ProviderInterface> $templateProviders
     */
    public function __construct(
        private IO\Messenger $messenger,
        private Builder\Config\ConfigReader $configReader,
        private Error\ErrorHandler $errorHandler,
        private Filesystem\Filesystem $filesystem,
        private string $targetDirectory,
        array $templateProviders = [],
    ) {
        if ([] === $templateProviders) {
            $templateProviders = $this->createDefaultTemplateProviders();
        }

        $this->templateProviders = $templateProviders;
    }

    public function run(): int
    {
        if (!$this->messenger->isInteractive()) {
            $this->messenger->error('This command cannot be run in non-interactive mode.');

            return self::FAILED;
        }

        $this->mirrorSourceFiles();

        $loader = Resource\Local\Composer::createClassLoader();
        $loader->register(true);

        $this->messenger->clearScreen();
        $this->messenger->welcome();

        try {
            // Select template source
            $templateSource = $this->selectTemplateSource();
            $templateSource->getProvider()->installTemplateSource($templateSource);
            $templateIdentifier = $templateSource->getPackage()->getName();

            // Create container
            $config = $this->configReader->readConfig($templateIdentifier);
            $container = $this->buildContainer($config);

            // Run project generation
            $generator = $container->get(Builder\Generator\Generator::class);
            $result = $generator->run($this->targetDirectory);

            // Show project generation result
            $this->messenger->decorateResult($result);

            // Early return if project generation was aborted
            if (!$result->isMirrored()) {
                return self::ABORTED;
            }

            $generator->cleanUp($result);
        } catch (Throwable $exception) {
            $this->errorHandler->handleException($exception);

            return self::FAILED;
        }

        return self::SUCCESSFUL;
    }

    private function mirrorSourceFiles(): void
    {
        $this->filesystem->mirror(
            Filesystem\Path::join($this->targetDirectory, Paths::PROJECT_SOURCES),
            Filesystem\Path::join($this->targetDirectory, '.build', Paths::PROJECT_SOURCES),
        );
    }

    /**
     * @throws Exception\InvalidTemplateSourceException
     */
    private function selectTemplateSource(): Template\TemplateSource
    {
        try {
            $templateProvider = $this->messenger->selectProvider($this->templateProviders);
            $templateSource = $this->messenger->selectTemplateSource($templateProvider);
        } catch (Exception\InvalidTemplateSourceException $exception) {
            $retry = $this->messenger->confirmTemplateSourceRetry($exception);

            $this->messenger->newLine();

            if ($retry) {
                return $this->selectTemplateSource();
            }

            throw $exception;
        }

        return $templateSource;
    }

    private function buildContainer(Builder\Config\Config $config): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        $factory = DependencyInjection\ContainerFactory::createFromConfig($config);
        $container = $factory->get();

        $container->set('app.config', $config);
        $container->set('app.messenger', $this->messenger);

        return $container;
    }

    /**
     * @return non-empty-list<Template\Provider\ProviderInterface>
     */
    private function createDefaultTemplateProviders(): array
    {
        return [
            new Template\Provider\PackagistProvider($this->messenger, $this->filesystem),
            new Template\Provider\CustomComposerProvider($this->messenger, $this->filesystem),
        ];
    }
}
