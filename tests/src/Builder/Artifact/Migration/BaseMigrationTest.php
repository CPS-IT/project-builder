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

namespace CPSIT\ProjectBuilder\Tests\Builder\Artifact\Migration;

use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use PHPUnit\Framework\Attributes\Test;

use function strrev;

/**
 * BaseMigrationTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BaseMigrationTest extends Framework\TestCase
{
    private Tests\Fixtures\DummyMigration $subject;

    /**
     * @var array<string, mixed>
     */
    private array $artifact;

    protected function setUp(): void
    {
        $this->subject = new Tests\Fixtures\DummyMigration();
        $this->artifact = [
            'foo' => [
                'baz' => 'hello world',
            ],
            'baz' => 'dummy',
        ];
    }

    #[Framework\Attributes\Test]
    public function remapValueCanRemapPathToOtherPath(): void
    {
        $this->subject->remapArguments = [
            'foo.baz',
            'baz',
        ];

        $expected = [
            'foo' => [],
            'baz' => 'hello world',
        ];

        self::assertSame($expected, $this->subject->migrate($this->artifact));
    }

    #[Framework\Attributes\Test]
    public function remapValueCanRemapPathToOtherValue(): void
    {
        $this->subject->remapArguments = [
            'foo.baz',
            null,
            'bye!',
        ];

        $expected = [
            'foo' => [
                'baz' => 'bye!',
            ],
            'baz' => 'dummy',
        ];

        self::assertSame($expected, $this->subject->migrate($this->artifact));
    }

    #[Framework\Attributes\Test]
    public function remapValueCanRemapPathToOtherValueFromCallable(): void
    {
        $this->subject->remapArguments = [
            'foo.baz',
            null,
            static fn (string $currentValue) => strrev($currentValue),
        ];

        $expected = [
            'foo' => [
                'baz' => 'dlrow olleh',
            ],
            'baz' => 'dummy',
        ];

        self::assertSame($expected, $this->subject->migrate($this->artifact));
    }

    #[Framework\Attributes\Test]
    public function remapValueCanRemapPathToOtherPathAndValue(): void
    {
        $this->subject->remapArguments = [
            'foo.baz',
            'baz',
            static fn (string $currentValue) => strrev($currentValue),
        ];

        $expected = [
            'foo' => [],
            'baz' => 'dlrow olleh',
        ];

        self::assertSame($expected, $this->subject->migrate($this->artifact));
    }
}
