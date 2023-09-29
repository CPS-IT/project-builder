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

namespace CPSIT\ProjectBuilder\Builder\Artifact;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Resource;

/**
 * ResultArtifact.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @extends Artifact<array{
 *     properties: array<string, mixed>,
 *     steps: list<array{type: string, applied: bool}>,
 *     processedFiles: list<array{source: string, target: string}>
 * }>
 */
final class ResultArtifact extends Artifact
{
    public function __construct(
        private readonly Builder\BuildResult $buildResult,
    ) {}

    public function dump(): array
    {
        return [
            'properties' => $this->buildResult->getInstructions()->getTemplateVariables(),
            'steps' => array_map(
                fn (Builder\Config\ValueObject\Step $step) => [
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
}
