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
use UnhandledMatchError;

/**
 * StringHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StringHelperTest extends Framework\TestCase
{
    /**
     * @param value-of<Src\StringCase> $case
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('convertCaseConvertsStringToGivenCaseDataProvider')]
    public function convertCaseConvertsStringToGivenCase(string $string, string $case, string $expected): void
    {
        self::assertSame($expected, Src\Helper\StringHelper::convertCase($string, $case));
    }

    #[Framework\Attributes\Test]
    public function convertCaseThrowsExceptionWhenConvertingToUnsupportedCase(): void
    {
        $this->expectException(UnhandledMatchError::class);

        /* @phpstan-ignore-next-line argument.type */
        Src\Helper\StringHelper::convertCase('foo', 'bar');
    }

    /**
     * @param array<string, string> $replacePairs
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('interpolateInterpolatedGivenStringWithKeyValuePairsDataProvider')]
    public function interpolateInterpolatedGivenStringWithKeyValuePairs(string $string, array $replacePairs, string $expected): void
    {
        self::assertSame($expected, Src\Helper\StringHelper::interpolate($string, $replacePairs));
    }

    /**
     * @return Generator<string, array{string, value-of<Src\StringCase>, string}>
     */
    public static function convertCaseConvertsStringToGivenCaseDataProvider(): Generator
    {
        yield 'lowercase' => ['foo_Bar-123 helloWorld', Src\StringCase::Lower->value, 'foo_bar-123 helloworld'];
        yield 'uppercase' => ['foo_Bar-123 helloWorld', Src\StringCase::Upper->value, 'FOO_BAR-123 HELLOWORLD'];
        yield 'snake case' => ['foo_Bar-123 helloWorld', Src\StringCase::Snake->value, 'foo_bar_123_hello_world'];
        yield 'upper camel case' => ['foo_Bar-123 helloWorld', Src\StringCase::UpperCamel->value, 'Foo_Bar-123HelloWorld'];
        yield 'lower camel case' => ['foo_Bar-123 helloWorld', Src\StringCase::LowerCamel->value, 'foo_Bar-123HelloWorld'];
    }

    /**
     * @return Generator<string, array{string, array<string, string>, string}>
     */
    public static function interpolateInterpolatedGivenStringWithKeyValuePairsDataProvider(): Generator
    {
        yield 'no placeholders' => ['foo', [], 'foo'];
        yield 'valid placeholder' => ['foo{bar}', ['bar' => 'foo'], 'foofoo'];
        yield 'invalid placeholder' => ['foo{foo}', ['bar' => 'foo'], 'foo{foo}'];
        yield 'multiple equal placeholders' => ['{bar}foo{bar}', ['bar' => 'foo'], 'foofoofoo'];
        yield 'multiple various placeholders' => ['foo{bar}{foo}', ['bar' => 'foo', 'foo' => 'bar'], 'foofoobar'];
    }
}
