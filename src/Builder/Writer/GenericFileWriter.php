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

namespace CPSIT\ProjectBuilder\Builder\Writer;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Helper;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

/**
 * GenericFileWriter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GenericFileWriter implements WriterInterface
{
    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
    ) {
    }

    public function write(
        Builder\BuildInstructions $instructions,
        Finder\SplFileInfo $file,
        string $targetFile = null,
    ): Finder\SplFileInfo {
        $targetDirectory = $instructions->getTemporaryDirectory();
        $targetFile = Helper\FilesystemHelper::createFileObject(
            $targetDirectory,
            $targetFile ?? $file->getRelativePathname(),
        );

        $this->filesystem->copy($file->getPathname(), $targetFile->getPathname());

        return $targetFile;
    }

    public static function supports(string $file): bool
    {
        return !str_ends_with($file, '.twig');
    }
}
