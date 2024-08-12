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

namespace CPSIT\ProjectBuilder\Tests\Builder\Writer;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Generator;
use JsonSerializable;
use PHPUnit\Framework;

/**
 * JsonFileWriterTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class JsonFileWriterTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Writer\JsonFileWriter $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Writer\JsonFileWriter::class);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('writeDumpsJsonToGivenFileDataProvider')]
    public function writeDumpsJsonToGivenFile(string|JsonSerializable $json, string $expected): void
    {
        $file = Src\Helper\FilesystemHelper::createFileObject(
            Src\Helper\FilesystemHelper::getNewTemporaryDirectory(),
            'test.json',
        );

        self::assertFileDoesNotExist($file->getPathname());
        self::assertTrue($this->subject->write($file, $json));
        self::assertJsonStringEqualsJsonFile($file->getPathname(), $expected);
    }

    /**
     * @return Generator<string, array{string|JsonSerializable, string}>
     */
    public static function writeDumpsJsonToGivenFileDataProvider(): Generator
    {
        yield 'json string' => ['{"foo":"baz"}', '{"foo":"baz"}'];
        yield 'serializable json object' => [
            new class implements JsonSerializable {
                /**
                 * @return array{'foo': string}
                 */
                public function jsonSerialize(): array
                {
                    return ['foo' => 'baz'];
                }
            },
            '{"foo":"baz"}',
        ];
    }
}
