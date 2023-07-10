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
use Symfony\Component\Finder;

use function array_map;
use function getenv;

/**
 * ArtifactGenerator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ArtifactGenerator
{
    public const VERSION = Artifact\Migration\Migration1688738958::VERSION;

    public function build(
        Finder\SplFileInfo $file,
        BuildResult $buildResult,
        Package\RootPackageInterface $rootPackage,
        int $version = self::VERSION,
    ): Artifact\Artifact {
        return new Artifact\Artifact(
            $this->generateBuildArtifact($file, $version),
            $this->generateTemplateArtifact($buildResult),
            $this->generateGeneratorArtifact($rootPackage),
            $this->generateResultArtifact($buildResult),
        );
    }

    private function generateBuildArtifact(Finder\SplFileInfo $file, int $version): Artifact\BuildArtifact
    {
        return new Artifact\BuildArtifact(
            $version,
            $file->getRelativePathname(),
            time(),
        );
    }

    private function generateTemplateArtifact(BuildResult $buildResult): Artifact\TemplateArtifact
    {
        $config = $buildResult->getInstructions()->getConfig();
        $package = $config->getTemplateSource()->getPackage();
        $provider = $config->getTemplateSource()->getProvider();

        $providerArtifact = [
            'type' => $provider::getType(),
            'url' => $provider->getUrl(),
        ];

        return new Artifact\TemplateArtifact(
            $config->getIdentifier(),
            $config->buildHash(),
            $this->generatePackageArtifact($package),
            $providerArtifact,
        );
    }

    private function generateGeneratorArtifact(Package\RootPackageInterface $rootPackage): Artifact\GeneratorArtifact
    {
        return new Artifact\GeneratorArtifact(
            $this->generatePackageArtifact($rootPackage),
            $this->determineExecutor(),
        );
    }

    private function generateResultArtifact(BuildResult $buildResult): Artifact\ResultArtifact
    {
        $steps = array_map(
            fn (Config\ValueObject\Step $step) => $this->mapStep($step, $buildResult),
            $buildResult->getInstructions()->getConfig()->getSteps(),
        );
        $processedFiles = array_map(
            $this->mapProcessedFile(...),
            $buildResult->getProcessedFiles(),
        );

        return new Artifact\ResultArtifact(
            $buildResult->getInstructions()->getTemplateVariables(),
            $steps,
            $processedFiles,
        );
    }

    /**
     * @return array{type: string, applied: bool}
     */
    private function mapStep(Config\ValueObject\Step $step, BuildResult $buildResult): array
    {
        return [
            'type' => $step->getType(),
            'applied' => $buildResult->isStepApplied($step->getType()),
        ];
    }

    /**
     * @return array{source: string, target: string}
     */
    private function mapProcessedFile(Resource\Local\ProcessedFile $processedFile): array
    {
        return [
            'source' => $processedFile->getOriginalFile()->getRelativePathname(),
            'target' => $processedFile->getTargetFile()->getRelativePathname(),
        ];
    }

    private function generatePackageArtifact(Package\PackageInterface $package): Artifact\PackageArtifact
    {
        return new Artifact\PackageArtifact(
            $package->getName(),
            $package->getPrettyVersion(),
            $package->getSourceReference(),
            $package->getSourceUrl(),
            $package->getDistUrl(),
        );
    }

    private function determineExecutor(): string
    {
        return match (getenv('PROJECT_BUILDER_EXECUTOR')) {
            'docker' => 'docker',
            default => 'composer',
        };
    }
}
