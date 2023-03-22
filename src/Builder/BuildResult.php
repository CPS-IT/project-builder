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

namespace CPSIT\ProjectBuilder\Builder;

use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function array_values;

/**
 * BuildResult.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildResult
{
    private bool $mirrored = false;
    private ?Finder\SplFileInfo $artifactFile = null;

    /**
     * @var array<string, Generator\Step\StepInterface>
     */
    private array $appliedSteps = [];

    public function __construct(
        private readonly BuildInstructions $instructions,
        private readonly ArtifactGenerator $artifactGenerator,
    ) {
    }

    public function getInstructions(): BuildInstructions
    {
        return $this->instructions;
    }

    public function isMirrored(): bool
    {
        return $this->mirrored;
    }

    public function setMirrored(bool $mirrored): self
    {
        $this->mirrored = $mirrored;

        return $this;
    }

    public function getArtifactFile(): ?Finder\SplFileInfo
    {
        return $this->artifactFile;
    }

    /**
     * @impure
     */
    public function setArtifactFile(?Finder\SplFileInfo $artifactFile): self
    {
        $this->artifactFile = $artifactFile;

        return $this;
    }

    public function getArtifact(): ?Artifact\Artifact
    {
        if (null !== $this->artifactFile) {
            return $this->generateArtifact($this->artifactFile);
        }

        return null;
    }

    /**
     * @return array<string, Generator\Step\StepInterface>
     */
    public function getAppliedSteps(): array
    {
        return $this->appliedSteps;
    }

    public function isStepApplied(Generator\Step\StepInterface|string $step): bool
    {
        if ($step instanceof Generator\Step\StepInterface) {
            $step = $step::getType();
        }

        return isset($this->appliedSteps[$step]);
    }

    public function applyStep(Generator\Step\StepInterface $step): void
    {
        $this->appliedSteps[$step::getType()] = $step;
    }

    /**
     * @return list<Resource\Local\ProcessedFile>
     */
    public function getProcessedFiles(string $withinPath = null): array
    {
        $files = [];

        foreach ($this->appliedSteps as $appliedStep) {
            if ($appliedStep instanceof Generator\Step\ProcessingStepInterface) {
                $files = [...$files, ...$appliedStep->getProcessedFiles()];
            }
        }

        if (null !== $withinPath) {
            $files = array_filter(
                $files,
                fn (Resource\Local\ProcessedFile $file): bool => Filesystem\Path::isBasePath(
                    $withinPath,
                    $file->getTargetFile()->getPathname(),
                ),
            );
        }

        return array_values($files);
    }

    public function getWrittenDirectory(): string
    {
        if ($this->mirrored) {
            return $this->instructions->getTargetDirectory();
        }

        return $this->instructions->getTemporaryDirectory();
    }

    private function generateArtifact(Finder\SplFileInfo $artifactFile): Artifact\Artifact
    {
        $composer = Resource\Local\Composer::createComposer(Helper\FilesystemHelper::getProjectRootPath());

        return $this->artifactGenerator->build($artifactFile, $this, $composer->getPackage());
    }
}
