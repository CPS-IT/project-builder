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
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

/**
 * GenerateBuildArtifactStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GenerateBuildArtifactStep extends AbstractStep implements StoppableStepInterface
{
    private const TYPE = 'generateBuildArtifact';

    private const DEFAULT_ARTIFACT_PATH = '.build/build-artifact.json';

    private bool $stopped = false;

    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
        private readonly IO\Messenger $messenger,
        private readonly IO\InputReader $inputReader,
    ) {
        parent::__construct();
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $artifactFile = $this->buildArtifactFile($buildResult);

        // Early return if build artifact already exists
        if ($this->filesystem->exists($artifactFile->getPathname())) {
            $this->messenger->error('The build artifact cannot be generated because the resulting file already exists.');
            $this->stopped = !$this->inputReader->ask('Continue without build artifact?');

            return !$this->stopped;
        }

        $buildResult->setArtifactFile($artifactFile);
        $buildResult->applyStep($this);

        return true;
    }

    public function revert(Builder\BuildResult $buildResult): void
    {
        // Intentionally left blank.
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }

    private function buildArtifactFile(Builder\BuildResult $buildResult): Finder\SplFileInfo
    {
        $artifactPath = $this->config->getOptions()->getArtifactPath() ?? self::DEFAULT_ARTIFACT_PATH;

        return Helper\FilesystemHelper::createFileObject(
            $buildResult->getInstructions()->getTargetDirectory(),
            $artifactPath,
        );
    }
}
