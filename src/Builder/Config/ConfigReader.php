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

namespace CPSIT\ProjectBuilder\Builder\Config;

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Paths;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;
use function array_key_exists;
use function dirname;

/**
 * ConfigReader.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigReader
{
    private const FILE_VARIANTS = [
        'config.yml',
        'config.yaml',
        'config.json',
    ];

    private string $templateDirectory;
    private Finder\Finder $finder;
    private ConfigFactory $factory;
    private bool $parsed = false;

    /**
     * @var array<string, Config>
     */
    private array $parsedConfig = [];

    private function __construct(ConfigFactory $factory)
    {
        $this->templateDirectory = Filesystem\Path::join(dirname(__DIR__, 3), Paths::PROJECT_TEMPLATES);
        $this->finder = $this->createFinder();
        $this->factory = $factory;
    }

    public static function create(): self
    {
        return new self(ConfigFactory::create());
    }

    /**
     * @throws Exception\InvalidConfigurationException
     */
    public function readConfig(string $identifier): Config
    {
        if (!$this->hasConfig($identifier)) {
            throw Exception\InvalidConfigurationException::create($identifier);
        }

        return $this->parsedConfig[$identifier];
    }

    public function hasConfig(string $identifier): bool
    {
        $this->parseConfig();

        return array_key_exists($identifier, $this->parsedConfig);
    }

    /**
     * @return array<string, string>
     */
    public function listTemplates(): array
    {
        $this->parseConfig();

        return array_map(fn (Config $config): string => $config->getName(), $this->parsedConfig);
    }

    /**
     * @throws Exception\InvalidConfigurationException
     */
    private function parseConfig(): void
    {
        if ($this->parsed) {
            return;
        }

        $this->parsedConfig = [];

        foreach ($this->finder as $configFile) {
            $config = $this->factory->buildFromFile($configFile->getPathname());

            if (array_key_exists($config->getIdentifier(), $this->parsedConfig)) {
                throw Exception\InvalidConfigurationException::forAmbiguousFiles($config->getIdentifier(), $this->parsedConfig[$config->getIdentifier()]->getDeclaringFile());
            }

            $this->parsedConfig[$config->getIdentifier()] = $config;
        }

        $this->parsed = true;
    }

    private function createFinder(): Finder\Finder
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in($this->templateDirectory)
            ->depth('== 1')
        ;

        // BC: Composer < 2.3 uses an old bundled version of symfony/finder
        // that does not yet support passing an iterable to Finder::name().
        foreach (self::FILE_VARIANTS as $variant) {
            $finder->name($variant);
        }

        return $finder;
    }
}
