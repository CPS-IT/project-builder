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
use Generator;
use PHPUnit\Framework;
use Symfony\Component\Filesystem;

use function chdir;
use function dirname;
use function getcwd;

/**
 * FilesystemHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemHelperTest extends Framework\TestCase
{
    private Filesystem\Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem\Filesystem();
    }

    #[Framework\Attributes\Test]
    public function createFileObjectReturnsFileObjectForGivenFile(): void
    {
        $baseDir = __DIR__;
        $relativePathname = basename(__FILE__);

        $actual = Src\Helper\FilesystemHelper::createFileObject($baseDir, $relativePathname);

        self::assertSame($relativePathname, $actual->getRelativePathname());
        self::assertSame('.', $actual->getRelativePath());
        self::assertSame($baseDir.'/'.$relativePathname, $actual->getPathname());
    }

    #[Framework\Attributes\Test]
    public function getNewTemporaryDirectoryReturnsUniqueTemporaryDirectory(): void
    {
        $prefix = sys_get_temp_dir();
        $actual = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        self::assertNotEmpty($prefix);
        self::assertDirectoryDoesNotExist($actual);
        self::assertStringStartsWith($prefix, $actual);
    }

    #[Framework\Attributes\Test]
    public function getPackageDirectoryReturnsPackagePathFromComposerPackageArtifact(): void
    {
        self::assertSame(dirname(__DIR__, 3), Src\Helper\FilesystemHelper::getPackageDirectory());
    }

    #[Framework\Attributes\Test]
    public function getWorkingDirectoryReturnsCurrentWorkingDirectory(): void
    {
        $cwd = dirname(__DIR__, 3);

        self::assertSame($cwd, Src\Helper\FilesystemHelper::getWorkingDirectory());

        chdir(__DIR__);

        self::assertSame(__DIR__, Src\Helper\FilesystemHelper::getWorkingDirectory());

        chdir($cwd);
    }

    #[Framework\Attributes\Test]
    public function resolveRelativePathReturnsAbsolutePathIfGivenPathIsAbsolute(): void
    {
        self::assertSame('/foo', Src\Helper\FilesystemHelper::resolveRelativePath('/foo'));
    }

    #[Framework\Attributes\Test]
    public function resolveRelativePathPrependsProjectRootPath(): void
    {
        $currentWorkingDirectory = getcwd();
        $projectRootPath = __DIR__.'/..';
        $expected = dirname(__DIR__).'/foo/baz';

        self::assertNotFalse($currentWorkingDirectory, 'Unable to get current working directory.');

        chdir($projectRootPath);

        self::assertSame($expected, Src\Helper\FilesystemHelper::resolveRelativePath('foo/baz'));

        chdir($currentWorkingDirectory);
    }

    #[Framework\Attributes\Test]
    public function isDirectoryEmptyReturnsTrueIfDirectoryDoesNotExist(): void
    {
        $directory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        self::assertDirectoryDoesNotExist($directory);
        self::assertTrue(Src\Helper\FilesystemHelper::isDirectoryEmpty($directory));
    }

    #[Framework\Attributes\Test]
    public function isDirectoryEmptyReturnsTrueIfDirectoryExistsAndIsEmpty(): void
    {
        $directory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        $this->filesystem->mkdir($directory);

        self::assertDirectoryExists($directory);
        self::assertTrue(Src\Helper\FilesystemHelper::isDirectoryEmpty($directory));

        $this->filesystem->remove($directory);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('isDirectoryEmptyReturnsFalseIfDirectoryHasFilesDataProvider')]
    public function isDirectoryEmptyReturnsFalseIfDirectoryHasFiles(string $filename): void
    {
        $directory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        $this->filesystem->mkdir($directory);
        $this->filesystem->dumpFile($directory.'/'.$filename, 'foo');

        self::assertDirectoryExists($directory);
        self::assertFileExists($directory.'/'.$filename);
        self::assertFalse(Src\Helper\FilesystemHelper::isDirectoryEmpty($directory));

        $this->filesystem->remove($directory);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('isDirectoryEmptyReturnsFalseIfDirectoryHasDirectoriesDataProvider')]
    public function isDirectoryEmptyReturnsFalseIfDirectoryHasDirectories(string $dirname): void
    {
        $directory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        $this->filesystem->mkdir($directory.'/'.$dirname);

        self::assertDirectoryExists($directory.'/'.$dirname);
        self::assertFalse(Src\Helper\FilesystemHelper::isDirectoryEmpty($directory));

        $this->filesystem->remove($directory);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function isDirectoryEmptyReturnsFalseIfDirectoryHasFilesDataProvider(): Generator
    {
        yield 'normal file' => ['foo'];
        yield 'dotfile' => ['.foo'];
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function isDirectoryEmptyReturnsFalseIfDirectoryHasDirectoriesDataProvider(): Generator
    {
        yield 'normal directory' => ['foo'];
        yield 'dot-directory' => ['.foo'];
    }

    #[Framework\Attributes\Test]
    public function resolveRelativePathReturnsGivenPathIfItIsAnAbsolutePath(): void
    {
        $path = '/foo/baz';

        self::assertSame($path, Src\Helper\FilesystemHelper::resolveRelativePath($path));
    }

    #[Framework\Attributes\Test]
    public function resolveRelativePathMakesRelativePathAbsolute(): void
    {
        $path = 'foo';
        $expected = dirname(__DIR__, 3).'/foo';

        self::assertSame($expected, Src\Helper\FilesystemHelper::resolveRelativePath($path));
    }
}
