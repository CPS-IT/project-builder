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

/**
 * ArrayHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArrayHelperTest extends TestCase
{
    /**
     * @test
     */
    public function getValueByPathReturnsValueAtGivenPath(): void
    {
        $subject = [
            'foo' => [
                'bar' => 'hello world!',
            ],
        ];

        self::assertSame('hello world!', Src\Helper\ArrayHelper::getValueByPath($subject, 'foo.bar'));
        self::assertSame(['bar' => 'hello world!'], Src\Helper\ArrayHelper::getValueByPath($subject, 'foo'));
        self::assertNull(Src\Helper\ArrayHelper::getValueByPath($subject, 'bar'));
    }

    /**
     * @test
     */
    public function setValueByPathSetsValueAtGivenPath(): void
    {
        $subject = [
            'foo' => [
                'bar' => 'hello world!',
            ],
        ];

        Src\Helper\ArrayHelper::setValueByPath($subject, 'foo.bar', 'bye!');

        self::assertSame('bye!', Src\Helper\ArrayHelper::getValueByPath($subject, 'foo.bar'));

        Src\Helper\ArrayHelper::setValueByPath($subject, 'bar', 'hello world!');

        self::assertSame('hello world!', Src\Helper\ArrayHelper::getValueByPath($subject, 'bar'));
        self::assertNull(Src\Helper\ArrayHelper::getValueByPath($subject, 'foobar'));
        self::assertSame(
            [
                'foo' => [
                    'bar' => 'bye!',
                ],
                'bar' => 'hello world!',
            ],
            $subject,
        );
    }
}
