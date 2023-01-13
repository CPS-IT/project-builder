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

use Composer\Package;

/**
 * GeneratorArtifact.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @phpstan-import-type TPackageArtifact from Artifact
 *
 * @extends Artifact<array{
 *     package: TPackageArtifact,
 *     executor: string
 * }>
 */
final class GeneratorArtifact extends Artifact
{
    public function __construct(
        private Package\RootPackageInterface $rootPackage,
    ) {
    }

    public function dump(): array
    {
        return [
            'package' => $this->decoratePackage($this->rootPackage),
            'executor' => $this->determineExecutor(),
        ];
    }

    private function determineExecutor(): string
    {
        return match (getenv('PROJECT_BUILDER_EXECUTOR')) {
            'docker' => 'docker',
            default => 'composer',
        };
    }
}
