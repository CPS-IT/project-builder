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
use CPSIT\ProjectBuilder\Exception;
use DirectoryIterator;
use OutOfBoundsException;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function dirname;
use function file_exists;

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
            // @codeCoverageIgnoreStart
        } catch (OutOfBoundsException) {
            $packageDirectory = null;
        }
        // @codeCoverageIgnoreEnd

        if (null === $packageDirectory) {
            $packageDirectory = dirname(__DIR__, 2);
        }

        return Filesystem\Path::canonicalize($packageDirectory);
    }

    public static function getWorkingDirectory(): string
    {
        /* @phpstan-ignore-next-line */
        if (method_exists(Util\Platform::class, 'getCwd')) {
            // Composer >= 2.3
            $cwd = Util\Platform::getCwd(true);
        } else {
            // Composer < 2.3
            $cwd = (string) getcwd(); // @codeCoverageIgnore
        }

        $cwd = realpath($cwd);

        if (false === $cwd) {
            throw Exception\FilesystemFailureException::forUnresolvableWorkingDirectory(); // @codeCoverageIgnore
        }

        return Filesystem\Path::canonicalize($cwd);
    }

    public static function resolveRelativePath(string $relativePath, bool $relativeToPackageDirectory = false): string
    {
        if (Filesystem\Path::isAbsolute($relativePath)) {
            return $relativePath;
        }

        // @todo Add test cases
        $basePath = $relativeToPackageDirectory ? self::getPackageDirectory() : self::getWorkingDirectory();

        return Filesystem\Path::makeAbsolute($relativePath, $basePath);
    }

    public static function isDirectoryEmpty(string $directory): bool
    {
        if (!file_exists($directory)) {
            return true;
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if (!$file->isDot()) {
                return false;
            }
        }

        return true;
    }
}
