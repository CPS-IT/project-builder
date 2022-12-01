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

use Composer\IO as ComposerIO;
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Paths;
use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\ExpressionLanguage;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

/**
 * MirrorProcessedFilesStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MirrorProcessedFilesStep extends AbstractStep implements ProcessingStepInterface, StoppableStepInterface
{
    use ProcessingFilesTrait;

    private const TYPE = 'mirrorProcessedFiles';

    private bool $stopped = false;

    public function __construct(
        ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        Filesystem\Filesystem $filesystem,
        private IO\Messenger $messenger,
    ) {
        parent::__construct();
        $this->expressionLanguage = $expressionLanguage;
        $this->filesystem = $filesystem;
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $instructions = $buildResult->getInstructions();

        if (!$this->messenger->confirmOverwrite($instructions->getTargetDirectory())) {
            $buildResult->setMirrored(false);

            $this->stopped = true;

            return false;
        }

        $this->messenger->newLine(ComposerIO\IOInterface::VERBOSE);

        foreach ($this->listFilesInTargetDirectory($instructions) as $file) {
            $this->messenger->progress(
                sprintf('Removing "%s" in target directory...', $file->getRelativePathname()),
            );

            $this->filesystem->remove($file->getPathname());

            $this->messenger->done();
        }

        foreach ($buildResult->getProcessedFiles($instructions->getTemporaryDirectory()) as $processedFile) {
            $mirroredFile = $this->mirrorFile($processedFile->getTargetFile(), $buildResult);
            $this->processedFiles[] = new Resource\Local\ProcessedFile($processedFile->getTargetFile(), $mirroredFile);
        }

        $this->messenger->progress('Removing temporary directory...');
        $this->filesystem->remove($instructions->getTemporaryDirectory());
        $this->messenger->done();

        $buildResult->applyStep($this);
        $buildResult->setMirrored(true);

        return true;
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

    private function mirrorFile(Finder\SplFileInfo $file, Builder\BuildResult $result): Finder\SplFileInfo
    {
        $this->messenger->progress(
            sprintf('Mirroring "%s" to target directory...', $file->getRelativePathname()),
        );

        $sourceFile = $file->getPathname();
        $targetFile = Helper\FilesystemHelper::createFileObject(
            $result->getInstructions()->getTargetDirectory(),
            $file->getRelativePathname(),
        );

        $this->filesystem->copy($sourceFile, $targetFile->getPathname(), true);

        $this->messenger->done();

        return $targetFile;
    }

    private function listFilesInTargetDirectory(Builder\BuildInstructions $instructions): Finder\Finder
    {
        $finder = Finder\Finder::create()
            ->ignoreDotFiles(false)
            ->in($instructions->getTargetDirectory())
            ->depth('== 0')
        ;

        // Keep protected library paths as they contain generator source files.
        // BC: Composer < 2.3 uses an old bundled version of symfony/finder
        // that does not yet support passing an iterable to Finder::notName().
        foreach (Paths::PROTECTED_PATHS as $path) {
            $finder->notName($path);
        }

        return $finder;
    }
}
