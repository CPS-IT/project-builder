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
use CPSIT\ProjectBuilder\Twig;
use Symfony\Component\ExpressionLanguage;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function fnmatch;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_ends_with;
use function substr;

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
    protected Twig\Renderer $renderer;

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
        }

        $this->processedFiles = [];
    }

    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    protected function shouldProcessFile(Finder\SplFileInfo $file, Builder\BuildInstructions $instructions): bool
    {
        $process = true;

        foreach ($this->config->getOptions()->getFileConditions() as $fileCondition) {
            if (!fnmatch($fileCondition->getPath(), $file->getRelativePathname())) {
                continue;
            }

            if ($this->fileConditionMatches($fileCondition, $instructions)) {
                return true;
            }

            $process = false;
        }

        return $process;
    }

    protected function findTargetFile(Finder\SplFileInfo $file, Builder\BuildInstructions $instructions): ?string
    {
        foreach ($this->config->getOptions()->getFileConditions() as $fileCondition) {
            if (null === $fileCondition->getTarget()) {
                continue;
            }

            if (!fnmatch($fileCondition->getPath(), $file->getRelativePathname())) {
                continue;
            }

            if (!$this->fileConditionMatches($fileCondition, $instructions)) {
                continue;
            }

            // Handle Twig syntax in target
            $target = $this->renderer->withDefaultTemplate($fileCondition->getTarget())->render($instructions);

            // Exchange current file's base path with target's base path
            if ($this->isConfiguredAsBasePath($fileCondition->getPath()) && $this->isConfiguredAsBasePath($target)) {
                $baseSourcePath = substr($fileCondition->getPath(), 0, -1);
                $baseTargetPath = substr($target, 0, -1);

                $target = preg_replace(
                    sprintf('#^%s#', preg_quote($baseSourcePath, '#')),
                    $baseTargetPath,
                    $file->getRelativePathname(),
                );
            }

            return $target;
        }

        return null;
    }

    protected function fileConditionMatches(
        Builder\Config\ValueObject\FileCondition $fileCondition,
        Builder\BuildInstructions $instructions,
    ): bool {
        return $fileCondition->conditionMatches(
            $this->expressionLanguage,
            $instructions->getTemplateVariables(),
            true,
        );
    }

    protected function isConfiguredAsBasePath(string $path): bool
    {
        return str_ends_with($path, '/*');
    }
}
