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

namespace CPSIT\ProjectBuilder\DependencyInjection;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Paths;
use Symfony\Component\Config;
use Symfony\Component\DependencyInjection;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function array_filter;
use function array_unshift;
use function basename;
use function dirname;
use function in_array;
use function iterator_to_array;

/**
 * ContainerFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @codeCoverageIgnore
 */
final class ContainerFactory
{
    /**
     * @param list<Finder\SplFileInfo> $resources
     */
    private function __construct(
        private array $resources,
        private ?string $containerPath = null,
        private bool $debug = false,
    ) {
    }

    /**
     * @param list<string> $resourcePaths
     */
    public static function create(array $resourcePaths = []): self
    {
        return new self(self::locateResources($resourcePaths));
    }

    public static function createFromConfig(Builder\Config\Config $config): self
    {
        $resourcePaths = [
            Filesystem\Path::join(
                Helper\FilesystemHelper::getPackageDirectory(),
                Paths::PROJECT_TEMPLATES,
                basename(dirname($config->getDeclaringFile())),
                Paths::TEMPLATE_SERVICE_CONFIG,
            ),
        ];

        return self::create($resourcePaths);
    }

    public static function createForTesting(string $testsRootPath = 'tests'): self
    {
        if (!Filesystem\Path::isAbsolute($testsRootPath)) {
            $testsRootPath = Filesystem\Path::join(
                Helper\FilesystemHelper::getPackageDirectory(),
                $testsRootPath,
            );
        }
        $resources = self::locateResources([
            Filesystem\Path::join(
                $testsRootPath,
                'config',
            ),
        ]);
        $containerPath = Filesystem\Path::join(
            Helper\FilesystemHelper::getPackageDirectory(),
            'var',
            'cache',
            'test-container.php',
        );

        $filesystem = new Filesystem\Filesystem();
        $filesystem->mkdir(dirname($containerPath));

        return new self($resources, $containerPath, true);
    }

    public function get(): DependencyInjection\ContainerInterface
    {
        $container = $this->buildContainer();

        if ($this->debug && null !== $this->containerPath) {
            $containerXmlFilename = Filesystem\Path::getFilenameWithoutExtension($this->containerPath);
            $containerXmlPath = Filesystem\Path::join(dirname($this->containerPath), $containerXmlFilename.'.xml');
            $container->addCompilerPass(new CompilerPass\ContainerBuilderDebugDumpPass($containerXmlPath));
        }

        $container->compile();

        if (null !== $this->containerPath) {
            $this->dumpContainer($container);
        }

        return $container;
    }

    private function buildContainer(): DependencyInjection\ContainerBuilder
    {
        $container = new DependencyInjection\ContainerBuilder();

        foreach ($this->resources as $resource) {
            $loader = $this->createLoader($resource, $container);
            $loader->load($resource->getFilename());
        }

        return $container;
    }

    private function dumpContainer(DependencyInjection\ContainerBuilder $container): void
    {
        $dumper = new DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
    }

    private function createLoader(
        Finder\SplFileInfo $resource,
        DependencyInjection\ContainerBuilder $container,
    ): Config\Loader\LoaderInterface {
        $locator = new Config\FileLocator($resource->getPath());

        return match ($resource->getExtension()) {
            'yaml', 'yml' => new DependencyInjection\Loader\YamlFileLoader($container, $locator),
            'php' => new DependencyInjection\Loader\PhpFileLoader($container, $locator),
            default => throw Exception\UnsupportedTypeException::create($resource->getExtension()),
        };
    }

    /**
     * @param list<string> $resourcePaths
     *
     * @return list<Finder\SplFileInfo>
     */
    private static function locateResources(array $resourcePaths = []): array
    {
        $defaultResourcePath = self::getDefaultResourcePath();

        if (!in_array($defaultResourcePath, $resourcePaths, true)) {
            array_unshift($resourcePaths, $defaultResourcePath);
        }

        $paths = array_filter($resourcePaths, 'is_dir');
        $finder = Finder\Finder::create()
            ->files()
            ->in($paths)
            ->name('/^services\.(ya?ml|php)$/')
            ->depth('== 0')
        ;

        return iterator_to_array($finder, false);
    }

    private static function getDefaultResourcePath(): string
    {
        return Filesystem\Path::join(Helper\FilesystemHelper::getPackageDirectory(), Paths::PROJECT_SERVICE_CONFIG);
    }
}
