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
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Paths;
use Symfony\Component\Config;
use Symfony\Component\DependencyInjection;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;
use function array_filter;
use function array_map;
use function array_unique;
use function assert;
use function dirname;
use function implode;
use function in_array;
use function is_string;
use function iterator_to_array;
use function md5;
use function sys_get_temp_dir;

/**
 * ContainerFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ContainerFactory
{
    private Config\ConfigCache $cache;

    /**
     * @var list<Finder\SplFileInfo>
     */
    private array $resources;
    private bool $debug;

    /**
     * @param list<Finder\SplFileInfo> $resources
     */
    private function __construct(Config\ConfigCache $cache, array $resources, bool $debug = false)
    {
        $this->cache = $cache;
        $this->resources = $resources;
        $this->debug = $debug;
    }

    /**
     * @param list<string> $resourcePaths
     */
    public static function create(array $resourcePaths = [], bool $debug = false): self
    {
        $resources = self::locateResources($resourcePaths);
        $cache = self::createCache($resources, $debug);

        return new self($cache, $resources, $debug);
    }

    public static function createFromConfig(Builder\Config\Config $config, bool $debug = false): self
    {
        $resourcePaths = [
            Filesystem\Path::join(
                Helper\FilesystemHelper::getProjectRootPath(),
                Paths::PROJECT_TEMPLATES,
                $config->getIdentifier(),
                Paths::TEMPLATE_SERVICE_CONFIG
            ),
        ];

        return self::create($resourcePaths, $debug);
    }

    public static function createForTesting(): self
    {
        $resources = self::locateResources();
        $cache = new Config\ConfigCache(
            Filesystem\Path::join(
                Helper\FilesystemHelper::getProjectRootPath(),
                'var',
                'cache',
                'test-container.php'
            ),
            true
        );

        $filesystem = new Filesystem\Filesystem();
        $filesystem->mkdir(dirname($cache->getPath()));

        return new self($cache, $resources, true);
    }

    public function get(): DependencyInjection\ContainerInterface
    {
        if (!$this->cache->isFresh()) {
            $container = $this->recreateCache();
        }

        require_once $this->cache->getPath();

        /** @noinspection PhpUndefinedClassInspection */
        $container = new ProjectServiceContainer();

        assert($container instanceof DependencyInjection\ContainerInterface);

        return $container;
    }

    private function recreateCache(): DependencyInjection\ContainerBuilder
    {
        $container = $this->buildContainer();

        if ($this->debug) {
            $containerXmlFilename = Filesystem\Path::getFilenameWithoutExtension($this->cache->getPath());
            $containerXmlPath = Filesystem\Path::join(dirname($this->cache->getPath()), $containerXmlFilename.'.xml');
            $container->addCompilerPass(new CompilerPass\ContainerBuilderDebugDumpPass($containerXmlPath));
        }

        $dumpedContainer = $this->dumpContainer($container);

        $this->cache->write($dumpedContainer, $container->getResources());

        return $container;
    }

    private function buildContainer(): DependencyInjection\ContainerBuilder
    {
        $container = new DependencyInjection\ContainerBuilder();
        $loader = $this->createLoader($container);

        foreach ($this->resources as $resource) {
            $loader->load($resource->getFilename());
        }

        return $container;
    }

    private function dumpContainer(DependencyInjection\ContainerBuilder $container): string
    {
        $container->compile();

        $dumper = new DependencyInjection\Dumper\PhpDumper($container);
        $dumpedContainer = $dumper->dump([
            'namespace' => __NAMESPACE__,
        ]);

        assert(is_string($dumpedContainer));

        return $dumpedContainer;
    }

    private function createLoader(DependencyInjection\ContainerBuilder $container): Config\Loader\LoaderInterface
    {
        $resourcePaths = array_unique(
            array_map(fn (Finder\SplFileInfo $file): string => $file->getPath(), $this->resources)
        );

        $locator = new Config\FileLocator($resourcePaths);
        $loaderResolver = new Config\Loader\LoaderResolver([
            new DependencyInjection\Loader\PhpFileLoader($container, $locator),
            new DependencyInjection\Loader\YamlFileLoader($container, $locator),
        ]);

        return new Config\Loader\DelegatingLoader($loaderResolver);
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
            $resourcePaths[] = $defaultResourcePath;
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

    /**
     * @param list<Finder\SplFileInfo> $resources
     */
    private static function createCache(array $resources, bool $debug = false): Config\ConfigCache
    {
        $resourcePaths = array_map(
            fn (Finder\SplFileInfo $fileInfo): string => $fileInfo->getPath(),
            $resources
        );
        $resourceHash = md5(implode(',', $resourcePaths));
        $cacheFile = Filesystem\Path::join(sys_get_temp_dir(), 'cpsit_project_builder_cache_'.$resourceHash.'.php');

        return new Config\ConfigCache($cacheFile, $debug);
    }

    private static function getDefaultResourcePath(): string
    {
        return Filesystem\Path::join(Helper\FilesystemHelper::getProjectRootPath(), Paths::PROJECT_SERVICE_CONFIG);
    }
}
