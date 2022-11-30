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
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Twig;
use Symfony\Component\Filesystem;

use function basename;
use function dirname;
use function explode;

/**
 * ShowNextStepsStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ShowNextStepsStep extends AbstractStep
{
    private const TYPE = 'showNextSteps';

    public function __construct(
        private Filesystem\Filesystem $filesystem,
        private IO\Messenger $messenger,
        private Twig\Renderer $renderer,
    ) {
        parent::__construct();
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $templateFile = $this->locateTemplateFile($buildResult);
        $renderer = $this->renderer->withRootPath(dirname($templateFile));

        if (!$renderer->canRender(basename($templateFile))) {
            throw Exception\InvalidConfigurationException::create('options.templateFile');
        }

        $nextSteps = explode(PHP_EOL, trim($renderer->render($buildResult->getInstructions(), basename($templateFile))));

        $this->messenger->section('Next steps');

        foreach ($nextSteps as $nextStep) {
            $this->messenger->write($nextStep);
        }

        $buildResult->applyStep($this);

        return true;
    }

    public function revert(Builder\BuildResult $buildResult): void
    {
        // There's nothing we can do here.
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }

    private function locateTemplateFile(Builder\BuildResult $buildResult): string
    {
        $templateFile = $this->config->getOptions()->getTemplateFile();

        if (null === $templateFile) {
            throw Exception\InvalidConfigurationException::create('options.templateFile');
        }

        $templateFilePath = Filesystem\Path::makeAbsolute(
            $templateFile,
            $buildResult->getInstructions()->getTemplateDirectory(),
        );

        if (!$this->filesystem->exists($templateFilePath)) {
            throw Exception\IOException::forMissingFile($templateFilePath);
        }

        return $templateFilePath;
    }
}
