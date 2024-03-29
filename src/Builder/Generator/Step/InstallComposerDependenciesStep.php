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
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

/**
 * InstallComposerDependenciesStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InstallComposerDependenciesStep extends AbstractStep
{
    private const TYPE = 'installComposerDependencies';

    public function __construct(
        private readonly Resource\Local\Composer $installer,
        private readonly IO\Messenger $messenger,
    ) {
        parent::__construct();
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $composerJson = Filesystem\Path::join($buildResult->getInstructions()->getTemplateDirectory(), 'composer.json');
        $exitCode = $this->runComposerInstall($composerJson);

        $buildResult->applyStep($this);

        return 0 === $exitCode;
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

    private function runComposerInstall(string $composerJson): int
    {
        $this->messenger->progress('Installing Composer dependencies...', ComposerIO\IOInterface::NORMAL);

        $output = new Console\Output\BufferedOutput();
        $exitCode = $this->installer->install($composerJson, false, $output);

        if (0 === $exitCode) {
            $this->messenger->done();

            return $exitCode;
        }

        if (!$this->messenger->isVerbose()) {
            $this->messenger->write($output->fetch());
        }

        return $exitCode;
    }
}
