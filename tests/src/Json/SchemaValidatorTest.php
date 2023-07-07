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

namespace CPSIT\ProjectBuilder\Tests\Json;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Generator;
use PHPUnit\Framework;

use function dirname;

/**
 * SchemaValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SchemaValidatorTest extends Tests\ContainerAwareTestCase
{
    private Src\Json\SchemaValidator $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Json\SchemaValidator::class);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('validateValidatesJsonDataProvider')]
    public function validateValidatesJson(mixed $data, bool $expected): void
    {
        $schemaFile = dirname(__DIR__).'/Fixtures/Files/test.schema.json';

        self::assertSame($expected, $this->subject->validate($data, $schemaFile)->isValid());
    }

    /**
     * @return Generator<string, array{mixed, bool}>
     */
    public static function validateValidatesJsonDataProvider(): Generator
    {
        yield 'valid json' => [(object) ['foo' => 'baz'], true];
        yield 'invalid json' => [null, false];
    }
}
