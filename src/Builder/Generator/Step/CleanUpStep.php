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
use CPSIT\ProjectBuilder\Paths;
use LogicException;
use Symfony\Component\Filesystem;

/**
 * CleanUpStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal This step is not meant to be used or referenced anywhere
 */
final class CleanUpStep extends AbstractStep
{
    private const TYPE = 'cleanUp';

    public function __construct(
        private Filesystem\Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $targetDirectory = $buildResult->getInstructions()->getTargetDirectory();

        $directoriesToRemove = array_map(
            fn (string $path): string => Filesystem\Path::makeAbsolute($path, $targetDirectory),
            Paths::PROTECTED_PATHS,
        );

        $this->filesystem->remove($directoriesToRemove);

        $buildResult->applyStep($this);

        return [] === array_filter(array_map([$this->filesystem, 'exists'], $directoriesToRemove));
    }

    public function revert(Builder\BuildResult $buildResult): void
    {
        throw new LogicException('A cleanup cannot be reverted since it\'s a destructive action.', 1652955151);
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        // Always deny support to assure step is only used internally
        return false;
    }
}
