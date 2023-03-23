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

namespace CPSIT\ProjectBuilder\Migration;

use Composer\Semver;
use CPSIT\Migrator;
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\DependencyInjection;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Paths;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Filesystem;

/**
 * ProjectMigrator
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProjectMigrator
{
    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
        private readonly Builder\ArtifactReader $artifactReader,
        private readonly Template\Provider\ProviderFactory $providerFactory,
        private readonly IO\Messenger $messenger,
    ) {
    }

    /**
     * @throws Exception\IOException
     * @throws Exception\InvalidArtifactException
     */
    public function migrate(string $projectDirectory, string $artifactPath = Paths::BUILD_ARTIFACT_DEFAULT_PATH)
    {
        // Validate project directory
        if (!$this->filesystem->exists($projectDirectory)) {
            throw Exception\IOException::forMissingDirectory($projectDirectory);
        }
        if (Helper\FilesystemHelper::isDirectoryEmpty($projectDirectory)) {
            throw Exception\IOException::forEmptyDirectory($projectDirectory);
        }

        $artifactFile = Filesystem\Path::join($projectDirectory, $artifactPath);

        // Check for existing artifact
        if (!$this->filesystem->exists($artifactFile)) {
            throw Exception\InvalidArtifactException::forFile($artifactFile);
        }

        // Restore artifact
        $artifact = $this->artifactReader->fromFile($artifactFile);

        // Restore configuration
        $config = $this->restoreConfigFromArtifact($artifact);

        // Build container
        $container = $this->buildContainer($config);
    }

    private function restoreConfigFromArtifact(Builder\Artifact\Artifact $artifact): Builder\Config\Config
    {
        $configReader = Builder\Config\ConfigReader::create();
        $provider = $this->providerFactory->get($artifact->template->provider['type']);

        if ($provider instanceof Template\Provider\CustomProviderInterface) {
            $provider->setUrl($artifact->template->provider['url']);
            $provider->setOptions($artifact->template->provider['options'] ?? []);
        }

        $templateSource = $provider->findTemplateSource(
            $artifact->template->package->name,
            new Semver\Constraint\Constraint('==', $artifact->template->package->version),
        );

        // Handle missing template source
        if (null === $templateSource) {
            // @todo throw exception
        }

        $provider->installTemplateSource($templateSource, false);

        return $configReader->readConfig($artifact->template->identifier);
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
