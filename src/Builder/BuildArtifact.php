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

use Composer\Package;
use CPSIT\ProjectBuilder\Resource;
use JsonSerializable;
use Symfony\Component\Finder;

use function array_map;
use function getenv;
use function time;

/**
 * BuildArtifact.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @phpstan-type TBuildArtifact array{ artifact: TArtifact, template: TTemplateArtifact, generator: TGeneratorArtifact, result: TResultArtifact}
 * @phpstan-type TArtifact array{version: int, path: string, date: int}
 * @phpstan-type TPackageArtifact array{name: string, version: string, sourceReference: string|null, sourceUrl: string|null, distUrl: string|null}
 * @phpstan-type TTemplateArtifact array{identifier: string, hash: string, package: TPackageArtifact, provider: array{name: string, url: string}}
 * @phpstan-type TGeneratorArtifact array{package: TPackageArtifact, executor: string}
 * @phpstan-type TResultArtifact array{properties: array<string, mixed>, steps: list<array{type: string, applied: bool}>, processedFiles: list<array{source: string, target: string}>}
 */
final class BuildArtifact implements JsonSerializable
{
    private const VERSION = 1;

    public function __construct(
        private Finder\SplFileInfo $path,
        private BuildResult $buildResult,
        private Package\RootPackageInterface $rootPackage,
    ) {
    }

    public function getPath(): Finder\SplFileInfo
    {
        return $this->path;
    }

    /**
     * @phpstan-return TBuildArtifact
     */
    public function dump(): array
    {
        return [
            'artifact' => [
                'version' => self::VERSION,
                'path' => $this->path->getRelativePathname(),
                'date' => time(),
            ],
            'template' => $this->buildTemplateArtifact(),
            'generator' => $this->buildGeneratorArtifact(),
            'result' => $this->buildResultArtifact(),
        ];
    }

    /**
     * @phpstan-return TTemplateArtifact
     */
    private function buildTemplateArtifact(): array
    {
        $config = $this->buildResult->getInstructions()->getConfig();
        $package = $config->getSource()->getPackage();
        $provider = $config->getSource()->getProvider();

        return [
            'identifier' => $config->getIdentifier(),
            'hash' => $config->buildHash(),
            'package' => $this->decoratePackage($package),
            'provider' => [
                'name' => $provider::getName(),
                'url' => $provider->getUrl(),
            ],
        ];
    }

    /**
     * @phpstan-return TGeneratorArtifact
     */
    private function buildGeneratorArtifact(): array
    {
        return [
            'package' => $this->decoratePackage($this->rootPackage),
            'executor' => $this->determineExecutor(),
        ];
    }

    /**
     * @phpstan-return TResultArtifact
     */
    private function buildResultArtifact(): array
    {
        return [
            'properties' => $this->buildResult->getInstructions()->getTemplateVariables(),
            'steps' => array_map(
                fn (Config\ValueObject\Step $step) => [
                    'type' => $step->getType(),
                    'applied' => $this->buildResult->isStepApplied($step->getType()),
                ],
                $this->buildResult->getInstructions()->getConfig()->getSteps(),
            ),
            'processedFiles' => array_map(
                fn (Resource\Local\ProcessedFile $processedFile) => [
                    'source' => $processedFile->getOriginalFile()->getRelativePathname(),
                    'target' => $processedFile->getTargetFile()->getRelativePathname(),
                ],
                $this->buildResult->getProcessedFiles(),
            ),
        ];
    }

    /**
     * @phpstan-return TPackageArtifact
     */
    private function decoratePackage(Package\PackageInterface $package): array
    {
        return [
            'name' => $package->getName(),
            'version' => $package->getVersion(),
            'sourceReference' => $package->getSourceReference(),
            'sourceUrl' => $package->getSourceUrl(),
            'distUrl' => $package->getDistUrl(),
        ];
    }

    private function determineExecutor(): string
    {
        return match (getenv('PROJECT_BUILDER_EXECUTOR')) {
            'docker' => 'docker',
            default => 'composer',
        };
    }

    /**
     * @phpstan-return TBuildArtifact
     */
    public function jsonSerialize(): array
    {
        return $this->dump();
    }
}
