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

namespace CPSIT\ProjectBuilder\Console\Command;

use Composer\Command;
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\DependencyInjection;
use CPSIT\ProjectBuilder\Error;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;
use Throwable;

/**
 * CreateProjectCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class CreateProjectCommand extends Command\BaseCommand
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
        private readonly IO\Messenger $messenger,
        private readonly Builder\Config\ConfigReader $configReader,
        private readonly Error\ErrorHandler $errorHandler,
        private readonly Filesystem\Filesystem $filesystem,
        array $templateProviders = [],
    ) {
        parent::__construct('project:create');

        if ([] === $templateProviders) {
            $templateProviders = $this->createDefaultTemplateProviders();
        }

        $this->templateProviders = $templateProviders;
    }

    public static function create(IO\Messenger $messenger): self
    {
        return new self(
            $messenger,
            Builder\Config\ConfigReader::create(),
            new Error\ErrorHandler($messenger),
            new Filesystem\Filesystem(),
        );
    }

    protected function configure(): void
    {
        $this->setDescription('Create a new project, based on a selected project template');

        $this->addArgument(
            'target-directory',
            Console\Input\InputArgument::REQUIRED,
            'Absolute path to a directory where to create the new project',
        );

        $this->addOption(
            'force',
            'f',
            Console\Input\InputOption::VALUE_NONE,
            'Force project creation even if target directory is not empty',
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        if (!$this->messenger->isInteractive()) {
            $this->messenger->error('This command cannot be run in non-interactive mode.');

            return self::FAILED;
        }

        $targetDirectory = Helper\FilesystemHelper::resolveRelativePath($input->getArgument('target-directory'));
        $force = $input->getOption('force');

        // Early return if target directory is not empty and should not be overwritten
        if (!$force
            && !Helper\FilesystemHelper::isDirectoryEmpty($targetDirectory)
            && !$this->messenger->confirmOverwrite($targetDirectory)
        ) {
            $this->messenger->error('Project creation aborted.');

            return self::ABORTED;
        }

        try {
            // Run project generation
            $generator = $this->prepareTemplate();
            $result = $generator->run($targetDirectory);

            // Show project generation result
            $this->messenger->decorateResult($result);

            // Early return if project generation was aborted
            if (!$result->isMirrored()) {
                return self::ABORTED;
            }

            $generator->dumpArtifact($result);
            $generator->cleanUp($result);
        } catch (Throwable $exception) {
            $this->errorHandler->handleException($exception);

            return self::FAILED;
        }

        return self::SUCCESSFUL;
    }

    private function prepareTemplate(): Builder\Generator\Generator
    {
        $this->messenger->clearScreen();
        $this->messenger->welcome();

        // Select template source
        $defaultTemplateProvider = reset($this->templateProviders);
        $templateSource = $this->selectTemplateSource($defaultTemplateProvider);
        $templateSource->getProvider()->installTemplateSource($templateSource);
        $templateIdentifier = $templateSource->getPackage()->getName();

        // Create container
        $config = $this->configReader->readConfig($templateIdentifier);
        $config->setTemplateSource($templateSource);
        $container = $this->buildContainer($config);

        return $container->get(Builder\Generator\Generator::class);
    }

    /**
     * @throws Exception\InvalidTemplateSourceException
     */
    private function selectTemplateSource(
        Template\Provider\ProviderInterface $templateProvider = null,
    ): Template\TemplateSource {
        try {
            $templateProvider ??= $this->messenger->selectProvider($this->templateProviders);
            $templateSource = $this->messenger->selectTemplateSource($templateProvider);
        } catch (Exception\InvalidTemplateSourceException $exception) {
            $retry = $this->messenger->confirmTemplateSourceRetry($exception);

            $this->messenger->newLine();

            if ($retry) {
                return $this->selectTemplateSource();
            }

            throw $exception;
        }

        if (null === $templateSource) {
            return $this->selectTemplateSource();
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
            new Template\Provider\ComposerProvider($this->messenger, $this->filesystem),
            new Template\Provider\VcsProvider($this->messenger, $this->filesystem),
        ];
    }
}
