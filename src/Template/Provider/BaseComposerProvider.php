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

namespace CPSIT\ProjectBuilder\Template\Provider;

use Composer\Factory;
use Composer\IO as ComposerIO;
use Composer\Package;
use Composer\Repository;
use Composer\Semver;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Paths;
use CPSIT\ProjectBuilder\Resource;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;
use Twig\Environment;
use Twig\Loader;

/**
 * BaseComposerProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class BaseComposerProvider implements ProviderInterface
{
    protected const PACKAGE_TYPE = 'project-builder-template';

    protected IO\Messenger $messenger;
    protected Filesystem\Filesystem $filesystem;
    protected Resource\Local\Composer $composer;
    protected Environment $renderer;
    protected ComposerIO\IOInterface $io;
    protected ?Repository\RepositoryInterface $repository = null;
    protected bool $acceptInsecureConnections = false;

    public function __construct(IO\Messenger $messenger, Filesystem\Filesystem $filesystem)
    {
        $this->messenger = $messenger;
        $this->filesystem = $filesystem;
        $this->composer = new Resource\Local\Composer($this->filesystem);
        $this->renderer = new Environment(
            new Loader\FilesystemLoader([
                Filesystem\Path::join(
                    Helper\FilesystemHelper::getProjectRootPath(),
                    Paths::PROJECT_INSTALLER
                ),
            ])
        );
        $this->io = new ComposerIO\BufferIO();
    }

    public function listTemplateSources(): array
    {
        $templateSources = [];

        if (null === $this->repository) {
            $this->repository = $this->createRepository();
        }

        $constraint = new Semver\Constraint\MatchAllConstraint();
        $searchResult = $this->repository->search(
            '',
            Repository\RepositoryInterface::SEARCH_FULLTEXT,
            self::PACKAGE_TYPE
        );

        foreach ($searchResult as ['name' => $packageName]) {
            $package = $this->repository->findPackage($packageName, $constraint);

            if (null !== $package && self::PACKAGE_TYPE === $package->getType()) {
                $templateSources[] = $this->createTemplateSource($package);
            }
        }

        return $templateSources;
    }

    public function installTemplateSource(Template\TemplateSource $templateSource): void
    {
        $composerJson = $this->createComposerJson($templateSource);
        $output = new Console\Output\BufferedOutput();

        $this->messenger->progress('Installing template source...', ComposerIO\IOInterface::NORMAL);

        $exitCode = $this->composer->install($composerJson, false, $output);

        if (0 !== $exitCode) {
            $this->messenger->failed();
            $this->messenger->write($output->fetch());

            throw Exception\InvalidTemplateSourceException::forFailedInstallation($templateSource);
        }

        $this->messenger->done();
        $this->messenger->newLine();

        // Make sure installed sources are handled by Composer's class loader
        $loader = Resource\Local\Composer::createClassLoader(dirname($composerJson));
        $loader->register(true);
    }

    protected function createTemplateSource(Package\BasePackage $package): Template\TemplateSource
    {
        return new Template\TemplateSource($this, $package);
    }

    protected function createComposerJson(Template\TemplateSource $templateSource): string
    {
        $targetDirectory = Helper\FilesystemHelper::getNewTemporaryDirectory();
        $targetFile = Filesystem\Path::join($targetDirectory, 'composer.json');
        $composerJson = $this->renderer->render('composer.json.twig', [
            'templateSources' => [$templateSource],
            'rootDir' => Helper\FilesystemHelper::getProjectRootPath(),
            'tempDir' => $targetDirectory,
            'providerUrl' => $this->getUrl(),
            'acceptInsecureConnections' => $this->acceptInsecureConnections,
        ]);

        $this->filesystem->dumpFile($targetFile, $composerJson);

        return $targetFile;
    }

    protected function createRepository(): Repository\RepositoryInterface
    {
        $config = Factory::createConfig($this->io);
        $repositoryManager = new Repository\RepositoryManager($this->io, $config, Factory::createHttpDownloader($this->io, $config));
        $repositoryManager->setRepositoryClass('composer', Repository\ComposerRepository::class);

        $repoConfig = [
            'type' => 'composer',
            'url' => $this->getUrl(),
        ];

        return Repository\RepositoryFactory::createRepo($this->io, $config, $repoConfig, $repositoryManager);
    }
}