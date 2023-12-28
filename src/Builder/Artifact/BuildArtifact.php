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
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Helper;
use Symfony\Component\Finder;

use function time;

/**
 * BuildArtifact.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @extends Artifact<array{
 *     artifact: array{version: int, file: string, date: int},
 *     template: TemplateArtifact,
 *     generator: GeneratorArtifact,
 *     result: ResultArtifact
 * }>
 */
final class BuildArtifact extends Artifact
{
    private const VERSION = 1;

    public function __construct(
        private readonly string $file,
        private readonly Builder\BuildResult $buildResult,
        private readonly Package\RootPackageInterface $rootPackage,
    ) {
    }

    public function dump(): array
    {
        return [
            'artifact' => [
                'version' => self::VERSION,
                'file' => $this->file,
                'date' => time(),
            ],
            'template' => new TemplateArtifact($this->buildResult),
            'generator' => new GeneratorArtifact($this->rootPackage),
            'result' => new ResultArtifact($this->buildResult),
        ];
    }

    public function getFile(): Finder\SplFileInfo
    {
        return Helper\FilesystemHelper::createFileObject($this->buildResult->getWrittenDirectory(), $this->file);
    }
}
