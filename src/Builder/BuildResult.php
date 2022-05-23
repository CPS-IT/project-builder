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

use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\Filesystem;
use function array_merge;

/**
 * BuildResult.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildResult
{
    private BuildInstructions $instructions;
    private bool $mirrored = false;

    /**
     * @var array<string, Generator\Step\StepInterface>
     */
    private array $appliedSteps = [];

    public function __construct(BuildInstructions $instructions)
    {
        $this->instructions = $instructions;
    }

    public function getInstructions(): BuildInstructions
    {
        return $this->instructions;
    }

    /**
     * @return list<Resource\Local\ProcessedFile>
     */
    public function getProcessedFiles(string $withinPath = null): array
    {
        $files = [];

        foreach ($this->appliedSteps as $appliedStep) {
            if ($appliedStep instanceof Generator\Step\ProcessingStepInterface) {
                $files = array_merge($files, $appliedStep->getProcessedFiles());
            }
        }

        if (null !== $withinPath) {
            $files = array_filter(
                $files,
                fn (Resource\Local\ProcessedFile $file): bool => Filesystem\Path::isBasePath(
                    $withinPath,
                    $file->getTargetFile()->getPathname()
                )
            );
        }

        return $files;
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

    /**
     * @return array<string, Generator\Step\StepInterface>
     */
    public function getAppliedSteps(): array
    {
        return $this->appliedSteps;
    }

    /**
     * @param Generator\Step\StepInterface|string $step
     */
    public function isStepApplied($step): bool
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

    public function getWrittenDirectory(): string
    {
        if ($this->mirrored) {
            return $this->instructions->getTargetDirectory();
        }

        return $this->instructions->getTemporaryDirectory();
    }
}
