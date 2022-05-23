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
use function assert;
use function dirname;

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

    private Mapper\TreeMapper $mapper;
    private JsonSchema\Validator $validator;

    private function __construct(Mapper\TreeMapper $mapper, JsonSchema\Validator $validator)
    {
        $this->mapper = $mapper;
        $this->validator = $validator;
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

    public function buildFromFile(string $file): Config
    {
        $content = file_get_contents($file);
        $type = $this->determineFileType($file);

        if (false === $content) {
            throw Exception\InvalidConfigurationException::forFile($file);
        }

        $config = $this->buildFromString($content, $type);
        $config->setDeclaringFile($file);

        return $config;
    }

    public function buildFromString(string $content, string $type): Config
    {
        $parsedContent = $this->parseContent($content, $type);
        $validationResult = $this->validateConfig($parsedContent);

        if (!$validationResult->isValid()) {
            throw Exception\InvalidConfigurationException::forValidationErrors($validationResult->error());
        }

        $source = $this->generateMapperSource($content, $type);
        $config = $this->mapper->map(Config::class, $source);

        assert($config instanceof Config);

        return $config;
    }

    private function validateConfig(stdClass $parsedContent): JsonSchema\ValidationResult
    {
        $schemaFile = Filesystem\Path::join(dirname(__DIR__, 3), Paths::PROJECT_SCHEMA_CONFIG);
        $schemaReference = 'file://'.$schemaFile;
        $schemaResolver = $this->validator->resolver();

        if (null === $schemaResolver) {
            $schemaResolver = new JsonSchema\Resolvers\SchemaResolver();
            $this->validator->setResolver($schemaResolver);
        }

        $schemaResolver->registerFile($schemaReference, $schemaFile);

        return $this->validator->validate($parsedContent, $schemaReference);
    }

    private function generateMapperSource(string $content, string $type): Mapper\Source\Source
    {
        switch ($type) {
            case FileType::YAML:
                return Mapper\Source\Source::yaml($content);

            case FileType::JSON:
                return Mapper\Source\Source::json($content);
        }

        throw Exception\UnsupportedTypeException::create($type);
    }

    private function parseContent(string $content, string $type): stdClass
    {
        switch ($type) {
            case FileType::YAML:
                $parsedContent = Yaml\Yaml::parse($content, Yaml\Yaml::PARSE_OBJECT_FOR_MAP);
                break;

            case FileType::JSON:
                $parsedContent = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                break;

            default:
                throw Exception\UnsupportedTypeException::create($type);
        }

        if (!($parsedContent instanceof stdClass)) {
            throw Exception\InvalidConfigurationException::forSource($content);
        }

        return $parsedContent;
    }

    private function determineFileType(string $file): string
    {
        $fileType = Filesystem\Path::getExtension($file, true);

        switch ($fileType) {
            case 'yml':
            case 'yaml':
                return FileType::YAML;

            case 'json':
                return FileType::JSON;
        }

        throw Exception\UnsupportedTypeException::create($fileType);
    }

    public function __destruct()
    {
        // @codeCoverageIgnoreStart
        if (null !== self::$cacheDirectory) {
            (new Filesystem\Filesystem())->remove(self::$cacheDirectory);
        }
        // @codeCoverageIgnoreEnd
    }
}
