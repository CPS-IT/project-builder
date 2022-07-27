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

namespace CPSIT\ProjectBuilder\Tests\Helper;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework\TestCase;

use function dirname;
use function putenv;

/**
 * FilesystemHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemHelperTest extends TestCase
{
    /**
     * @test
     */
    public function createFileObjectReturnsFileObjectForGivenFile(): void
    {
        $baseDir = __DIR__;
        $relativePathname = basename(__FILE__);

        $actual = Src\Helper\FilesystemHelper::createFileObject($baseDir, $relativePathname);

        self::assertSame($relativePathname, $actual->getRelativePathname());
        self::assertSame('.', $actual->getRelativePath());
        self::assertSame($baseDir.'/'.$relativePathname, $actual->getPathname());
    }

    /**
     * @test
     */
    public function getNewTemporaryDirectoryReturnsUniqueTemporaryDirectory(): void
    {
        $actual = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        self::assertDirectoryDoesNotExist($actual);
        self::assertStringStartsWith(sys_get_temp_dir(), $actual);
    }

    /**
     * @test
     */
    public function getProjectRootPathReturnsProjectRootPathFromEnvironmentVariable(): void
    {
        $projectRootPath = __DIR__.'/..';

        putenv('PROJECT_BUILDER_ROOT_PATH='.$projectRootPath);

        self::assertSame(dirname(__DIR__), Src\Helper\FilesystemHelper::getProjectRootPath());

        putenv('PROJECT_BUILDER_ROOT_PATH');
    }

    /**
     * @test
     */
    public function getProjectRootPathReturnsProjectRootPathFromComposerPackageArtifact(): void
    {
        self::assertSame(dirname(__DIR__, 3), Src\Helper\FilesystemHelper::getProjectRootPath());
    }
}
