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
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Paths;
use CuyZ\Valinor\Cache;
use CuyZ\Valinor\Mapper;
use CuyZ\Valinor\MapperBuilder;
use Opis\JsonSchema;
use stdClass;
use Symfony\Component\Filesystem;
use Symfony\Component\Yaml;

use function json_decode;

/**
 * ConfigFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ConfigFactory
{
    private static ?string $cacheDirectory = null;

    private function __construct(
        private Mapper\TreeMapper $mapper,
        private JsonSchema\Validator $validator,
    ) {
    }

    public static function create(): self
    {
        if (null === self::$cacheDirectory) {
            self::$cacheDirectory = Helper\FilesystemHelper::getNewTemporaryDirectory();
        }

        $mapper = (new MapperBuilder())
            ->withCache(new Cache\FileSystemCache(self::$cacheDirectory))
            ->mapper()
        ;

        return new self($mapper, new JsonSchema\Validator());
    }

    public function buildFromFile(string $file, string $identifier): Config
    {
        $type = $this->determineFileType($file);
        $content = file_get_contents($file);

        if (false === $content) {
            // @codeCoverageIgnoreStart
            throw Exception\InvalidConfigurationException::forFile($file);
            // @codeCoverageIgnoreEnd
        }

        $config = $this->buildFromString($content, $identifier, $type);

        return $config->setDeclaringFile($file);
    }

    public function buildFromString(string $content, string $identifier, string $type): Config
    {
        $parsedContent = $this->parseContent($content, $type);
        $validationResult = $this->validateConfig($parsedContent);

        if (!$validationResult->isValid()) {
            throw Exception\InvalidConfigurationException::forValidationErrors($validationResult->error());
        }

        $source = $this->generateMapperSource($content, $identifier, $type);

        return $this->mapper->map(Config::class, $source);
    }

    private function validateConfig(stdClass $parsedContent): JsonSchema\ValidationResult
    {
        $schemaFile = Filesystem\Path::join(Helper\FilesystemHelper::getProjectRootPath(), Paths::PROJECT_SCHEMA_CONFIG);
        $schemaReference = 'file://'.$schemaFile;
        $schemaResolver = $this->validator->resolver();

        // @codeCoverageIgnoreStart
        if (null === $schemaResolver) {
            $schemaResolver = new JsonSchema\Resolvers\SchemaResolver();
            $this->validator->setResolver($schemaResolver);
        }
        // @codeCoverageIgnoreEnd

        $schemaResolver->registerFile($schemaReference, $schemaFile);

        return $this->validator->validate($parsedContent, $schemaReference);
    }

    private function generateMapperSource(string $content, string $identifier, string $type): Mapper\Source\Source
    {
        $parsedContent = match ($type) {
            FileType::YAML => Yaml\Yaml::parse($content),
            FileType::JSON => json_decode($content, true, 512, JSON_THROW_ON_ERROR),
            default => throw Exception\UnsupportedTypeException::create($type),
        };

        // @codeCoverageIgnoreStart
        if (!is_array($parsedContent)) {
            throw Exception\InvalidConfigurationException::forSource($content);
        }
        // @codeCoverageIgnoreEnd

        // Enforce custom identifier
        $parsedContent['identifier'] = $identifier;

        return Mapper\Source\Source::array($parsedContent);
    }

    private function parseContent(string $content, string $type): stdClass
    {
        $parsedContent = match ($type) {
            FileType::YAML => Yaml\Yaml::parse($content, Yaml\Yaml::PARSE_OBJECT_FOR_MAP),
            FileType::JSON => json_decode($content, false, 512, JSON_THROW_ON_ERROR),
            default => throw Exception\UnsupportedTypeException::create($type),
        };

        if (!($parsedContent instanceof stdClass)) {
            throw Exception\InvalidConfigurationException::forSource($content);
        }

        return $parsedContent;
    }

    private function determineFileType(string $file): string
    {
        $fileType = Filesystem\Path::getExtension($file, true);

        return match ($fileType) {
            'yml', 'yaml' => FileType::YAML,
            'json' => FileType::JSON,
            default => throw Exception\UnsupportedTypeException::create($fileType),
        };
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        if (null !== self::$cacheDirectory) {
            (new Filesystem\Filesystem())->remove(self::$cacheDirectory);
        }
    }
}
