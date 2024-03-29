<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Tests\Builder\Config;

use CPSIT\ProjectBuilder as Src;
use Generator;
use PHPUnit\Framework;

/**
 * FileTypeTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FileTypeTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function fromExtensionThrowsExceptionIfGivenExtensionIsNotSupported(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "php" is not supported.');

        Src\Builder\Config\FileType::fromExtension('php');
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('fromExtensionReturnsFileTypeOfGivenExtensionDataProvider')]
    public function fromExtensionReturnsFileTypeOfGivenExtension(
        string $extension,
        Src\Builder\Config\FileType $expected,
    ): void {
        self::assertSame($expected, Src\Builder\Config\FileType::fromExtension($extension));
    }

    #[Framework\Attributes\Test]
    public function fromFileThrowsExceptionIfExtensionOfGivenFileIsNotSupported(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "php" is not supported.');

        Src\Builder\Config\FileType::fromFile(__FILE__);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('fromFileReturnsFileTypeOfGivenFileDataProvider')]
    public function fromFileReturnsFileTypeOfGivenFile(
        string $file,
        Src\Builder\Config\FileType $expected,
    ): void {
        self::assertSame($expected, Src\Builder\Config\FileType::fromFile($file));
    }

    /**
     * @return Generator<string, array{string, Src\Builder\Config\FileType}>
     */
    public static function fromExtensionReturnsFileTypeOfGivenExtensionDataProvider(): Generator
    {
        yield 'json' => ['json', Src\Builder\Config\FileType::Json];
        yield 'yml' => ['yml', Src\Builder\Config\FileType::Yaml];
        yield 'yaml' => ['yaml', Src\Builder\Config\FileType::Yaml];
    }

    /**
     * @return Generator<string, array{string, Src\Builder\Config\FileType}>
     */
    public static function fromFileReturnsFileTypeOfGivenFileDataProvider(): Generator
    {
        yield 'json' => ['foo.json', Src\Builder\Config\FileType::Json];
        yield 'yml' => ['foo.yml', Src\Builder\Config\FileType::Yaml];
        yield 'yaml' => ['foo.yaml', Src\Builder\Config\FileType::Yaml];
    }
}
