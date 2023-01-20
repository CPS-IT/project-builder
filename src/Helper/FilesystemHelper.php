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

namespace CPSIT\ProjectBuilder\Helper;

use Composer\InstalledVersions;
use Composer\Util;
use OutOfBoundsException;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function dirname;
use function getenv;

/**
 * FilesystemHelper.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemHelper
{
    public static function createFileObject(string $baseDir, string $relativePathname): Finder\SplFileInfo
    {
        return new Finder\SplFileInfo(
            Filesystem\Path::join($baseDir, $relativePathname),
            dirname($relativePathname),
            $relativePathname,
        );
    }

    public static function getNewTemporaryDirectory(): string
    {
        do {
            $dir = Filesystem\Path::join(sys_get_temp_dir(), uniqid('cpsit_project_builder_'));
        } while (is_dir($dir));

        return $dir;
    }

    public static function getPackageDirectory(): string
    {
        try {
            $packageDirectory = InstalledVersions::getInstallPath('cpsit/project-builder');
        } catch (OutOfBoundsException) {
            $packageDirectory = null;
        }

        if (null === $packageDirectory) {
            $packageDirectory = dirname(__DIR__, 2);
        }

        return Filesystem\Path::canonicalize($packageDirectory);
    }

    public static function getWorkingDirectory(): string
    {
        return Filesystem\Path::canonicalize(Util\Platform::getCwd());
    }
}
