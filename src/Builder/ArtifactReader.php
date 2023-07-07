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

namespace CPSIT\ProjectBuilder\Builder;

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Json;
use CPSIT\ProjectBuilder\Paths;
use CuyZ\Valinor;
use DateTimeInterface;
use Opis\JsonSchema;
use ReflectionObject;
use Symfony\Component\Filesystem;

use function array_filter;
use function array_values;
use function file_get_contents;
use function implode;
use function is_array;
use function is_numeric;
use function json_decode;
use function ksort;
use function range;

/**
 * ArtifactReader.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArtifactReader
{
    private readonly Valinor\Mapper\TreeMapper $mapper;

    /**
     * @var list<Artifact\Migration\Migration>
     */
    private readonly array $migrations;

    /**
     * @param iterable<Artifact\Migration\Migration> $migrations
     */
    public function __construct(
        iterable $migrations,
        private readonly Filesystem\Filesystem $filesystem,
        private readonly Json\SchemaValidator $schemaValidator,
    ) {
        $this->mapper = $this->createMapper();
        $this->migrations = $this->orderMigrations($migrations);
    }

    /**
     * @throws Exception\InvalidArtifactException
     * @throws Valinor\Mapper\MappingError
     */
    public function fromFile(string $file): Artifact\Artifact
    {
        $artifact = $this->parseArtifactFile($file);
        $migratedArtifact = $this->performMigrations($artifact);
        $validationResult = $this->schemaValidator->validate(
            JsonSchema\Helper::toJSON($migratedArtifact),
            Paths::BUILD_ARTIFACT_SCHEMA,
        );

        // Validate migrated artifact
        if (!$validationResult->isValid()) {
            throw Exception\InvalidArtifactException::forValidationErrors($validationResult->error());
        }

        // Create mapper source from artifact file
        $source = Valinor\Mapper\Source\Source::array($migratedArtifact);

        return $this->mapper->map(Artifact\Artifact::class, $source);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception\InvalidArtifactException
     */
    private function parseArtifactFile(string $file): array
    {
        if (!$this->filesystem->exists($file)) {
            throw Exception\InvalidArtifactException::forFile($file);
        }

        $content = file_get_contents($file);

        // @codeCoverageIgnoreStart
        if (false === $content) {
            throw Exception\InvalidArtifactException::forFile($file);
        }
        // @codeCoverageIgnoreEnd

        $artifact = json_decode($content, true);

        // Assure artifact is an array
        if (!is_array($artifact)) {
            throw Exception\InvalidArtifactException::forFile($file);
        }

        // Assure artifact is an associative array
        if ($artifact !== array_filter($artifact, 'is_string', ARRAY_FILTER_USE_KEY)) {
            throw Exception\InvalidArtifactException::forFile($file);
        }

        return $artifact;
    }

    /**
     * @param array<string, mixed> $artifact
     *
     * @return array<string, mixed>
     *
     * @throws Exception\InvalidArtifactException
     */
    private function performMigrations(array $artifact): array
    {
        $artifactVersion = $this->determineArtifactVersion($artifact);
        $migrationPath = range($artifactVersion, ArtifactGenerator::VERSION);

        foreach ($migrationPath as $sourceVersion) {
            foreach ($this->migrations as $migration) {
                if ($sourceVersion === $migration::getSourceVersion()) {
                    $artifact = $migration->migrate($artifact);
                }
            }
        }

        return $artifact;
    }

    /**
     * @param array<string, mixed> $artifact
     *
     * @throws Exception\InvalidArtifactException
     */
    private function determineArtifactVersion(array $artifact): int
    {
        $version = Helper\ArrayHelper::getValueByPath($artifact, 'artifact.version');

        if (!is_numeric($version)) {
            throw Exception\InvalidArtifactException::forInvalidVersion();
        }

        return (int) $version;
    }

    private function createMapper(): Valinor\Mapper\TreeMapper
    {
        return (new Valinor\MapperBuilder())
            ->allowPermissiveTypes()
            ->supportDateFormats(DateTimeInterface::ATOM)
            ->mapper()
        ;
    }

    /**
     * @param iterable<Artifact\Migration\Migration> $migrations
     *
     * @return list<Artifact\Migration\Migration>
     */
    private function orderMigrations(iterable $migrations): array
    {
        $prefixedMigrations = [];

        foreach ($migrations as $migration) {
            $reflectionObject = new ReflectionObject($migration);
            $migrationIdentifier = implode('_', [
                $migration::getSourceVersion(),
                $migration::getTargetVersion(),
                $reflectionObject->getShortName(),
            ]);
            $prefixedMigrations[$migrationIdentifier] = $migration;
        }

        ksort($prefixedMigrations);

        return array_values($prefixedMigrations);
    }
}
