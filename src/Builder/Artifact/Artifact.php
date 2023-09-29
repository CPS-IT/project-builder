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

use JsonSerializable;

/**
 * Artifact.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @phpstan-type ArtifactType array{
 *     artifact: BuildArtifact,
 *     template: TemplateArtifact,
 *     generator: GeneratorArtifact,
 *     result: ResultArtifact
 * }
 */
final class Artifact implements JsonSerializable
{
    public function __construct(
        public readonly BuildArtifact $artifact,
        public readonly TemplateArtifact $template,
        public readonly GeneratorArtifact $generator,
        public readonly ResultArtifact $result,
    ) {}

    /**
     * @phpstan-return ArtifactType
     */
    public function dump(): array
    {
        return [
            'artifact' => $this->artifact,
            'template' => $this->template,
            'generator' => $this->generator,
            'result' => $this->result,
        ];
    }

    /**
     * @phpstan-return ArtifactType
     */
    public function jsonSerialize(): array
    {
        return $this->dump();
    }
}
