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

namespace CPSIT\ProjectBuilder\Builder\Generator\Step;

use CPSIT\ProjectBuilder\Builder;
use Symfony\Component\Filesystem;

/**
 * DumpBuildArtifactStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal This step is not meant to be used or referenced anywhere
 */
final class DumpBuildArtifactStep extends AbstractStep
{
    private const TYPE = 'dumpBuildArtifact';

    public function __construct(
        private Filesystem\Filesystem $filesystem,
        private Builder\Writer\JsonFileWriter $writer,
    ) {
        parent::__construct();
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        if (null === $buildResult->getBuildArtifact()) {
            return true;
        }

        return $this->writer->write($buildResult->getBuildArtifact()->getPath(), $buildResult->getBuildArtifact());
    }

    public function revert(Builder\BuildResult $buildResult): void
    {
        if (null !== $buildResult->getBuildArtifact()) {
            $this->filesystem->remove($buildResult->getBuildArtifact()->getPath()->getPathname());
        }
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }
}
