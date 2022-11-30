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

namespace CPSIT\ProjectBuilder\Builder\Generator\Step;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\ExpressionLanguage;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

/**
 * ProcessingFilesTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait ProcessingFilesTrait
{
    protected ExpressionLanguage\ExpressionLanguage $expressionLanguage;
    protected Filesystem\Filesystem $filesystem;

    /**
     * @var list<Resource\Local\ProcessedFile>
     */
    protected array $processedFiles = [];

    public function revert(Builder\BuildResult $buildResult): void
    {
        if (!$buildResult->isStepApplied($this)) {
            return;
        }

        foreach ($this->processedFiles as $index => $processedFile) {
            $this->filesystem->remove($processedFile->getTargetFile()->getPathname());
            unset($this->processedFiles[$index]);
        }
    }

    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    protected function shouldProcessFile(Finder\SplFileInfo $file, Builder\BuildInstructions $instructions): bool
    {
        foreach ($this->config->getOptions()->getFileConditions() as $fileCondition) {
            if (!fnmatch($fileCondition->getPath(), $file->getRelativePathname())) {
                continue;
            }

            return $fileCondition->conditionMatches(
                $this->expressionLanguage,
                $instructions->getTemplateVariables(),
                true,
            );
        }

        return true;
    }
}
