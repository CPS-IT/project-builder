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
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Resource;
use CPSIT\ProjectBuilder\Twig;
use Symfony\Component\ExpressionLanguage;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

/**
 * ProcessSourceFilesStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessSourceFilesStep extends AbstractStep implements ProcessingStepInterface
{
    use ProcessingFilesTrait;

    private const TYPE = 'processSourceFiles';

    public function __construct(
        ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        Filesystem\Filesystem $filesystem,
        Twig\Renderer $renderer,
        private readonly IO\Messenger $messenger,
        private readonly Builder\Writer\WriterFactory $writerFactory,
    ) {
        parent::__construct();
        $this->expressionLanguage = $expressionLanguage;
        $this->filesystem = $filesystem;
        $this->renderer = $renderer;
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $instructions = $buildResult->getInstructions();

        foreach ($this->listSourceFiles($instructions) as $sourceFile) {
            $this->messenger->progress(
                sprintf('Processing source file "%s"...', $sourceFile->getRelativePathname()),
            );

            $writer = $this->writerFactory->get($sourceFile->getPathname());
            $processedFile = $writer->write(
                $instructions,
                $sourceFile,
                $this->findTargetFile($sourceFile, $instructions),
            );

            $this->processedFiles[] = new Resource\Local\ProcessedFile($sourceFile, $processedFile);

            $buildResult->applyStep($this);

            $this->messenger->done();
        }

        return true;
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }

    private function listSourceFiles(Builder\BuildInstructions $instructions): Finder\Finder
    {
        return Finder\Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->in($instructions->getSourceDirectory())
            ->filter(fn(Finder\SplFileInfo $file): bool => $this->shouldProcessFile($file, $instructions))
            ->sortByName()
        ;
    }
}
