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

it('returns value at given path', function () {
    $subject = [
        'foo' => [
            'bar' => 'hello world!',
        ],
    ];

    expect(Helper\ArrayHelper::getValueByPath($subject, 'foo.bar'))->toBe('hello world!');
    expect(Helper\ArrayHelper::getValueByPath($subject, 'foo'))->toBe(['bar' => 'hello world!']);
    expect(Helper\ArrayHelper::getValueByPath($subject, 'bar'))->toBeNull();
});

it('sets value at given path', function () {
    $subject = [
        'foo' => [
            'bar' => 'hello world!',
        ],
    ];

    Helper\ArrayHelper::setValueByPath($subject, 'foo.bar', 'bye!');

    expect(Helper\ArrayHelper::getValueByPath($subject, 'foo.bar'))->toBe('bye!');

    Helper\ArrayHelper::setValueByPath($subject, 'bar', 'hello world!');

    expect(Helper\ArrayHelper::getValueByPath($subject, 'bar'))->toBe('hello world!');
    expect(Helper\ArrayHelper::getValueByPath($subject, 'foobar'))->toBeNull();
    expect($subject)->toBe([
        'foo' => [
            'bar' => 'bye!',
        ],
        'bar' => 'hello world!',
    ]);
});
