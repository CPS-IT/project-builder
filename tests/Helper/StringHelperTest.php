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

namespace CPSIT\ProjectBuilder;

it('converts a string to a given case', function (string $string, string $case, string $expected) {
    expect(Helper\StringHelper::convertCase($string, $case))->toBe($expected);
})->with([
    'lowercase' => ['foo_Bar-123 helloWorld', StringCase::LOWER, 'foo_bar-123 helloworld'],
    'uppercase' => ['foo_Bar-123 helloWorld', StringCase::UPPER, 'FOO_BAR-123 HELLOWORLD'],
    'snake case' => ['foo_Bar-123 helloWorld', StringCase::SNAKE, 'foo_bar_123_hello_world'],
    'upper camel case' => ['foo_Bar-123 helloWorld', StringCase::UPPER_CAMEL, 'Foo_Bar-123HelloWorld'],
    'lower camel case' => ['foo_Bar-123 helloWorld', StringCase::LOWER_CAMEL, 'foo_Bar-123HelloWorld'],
]);

it('throws an exception when converting to an unsupported case', function () {
    expect(fn () => Helper\StringHelper::convertCase('foo', 'bar'))
        ->toThrow(Exception\UnsupportedTypeException::class, 'The type "bar" is not supported.')
    ;
});

it('interpolates a given string with key-value pairs', function (string $string, array $replacePairs, string $expected) {
    expect(Helper\StringHelper::interpolate($string, $replacePairs))->toBe($expected);
})->with([
    'no placeholders' => ['foo', [], 'foo'],
    'valid placeholder' => ['foo{bar}', ['bar' => 'foo'], 'foofoo'],
    'invalid placeholder' => ['foo{foo}', ['bar' => 'foo'], 'foo{foo}'],
    'multiple equal placeholders' => ['{bar}foo{bar}', ['bar' => 'foo'], 'foofoofoo'],
    'multiple various placeholders' => ['foo{bar}{foo}', ['bar' => 'foo', 'foo' => 'bar'], 'foofoobar'],
]);
