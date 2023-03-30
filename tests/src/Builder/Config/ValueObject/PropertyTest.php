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

namespace CPSIT\ProjectBuilder\Tests\Builder\Config\ValueObject;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework\TestCase;

/**
 * PropertyTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PropertyTest extends TestCase
{
    private Src\Builder\Config\ValueObject\Property $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Config\ValueObject\Property(
            'identifier',
            'name',
            'path',
            'if',
            'value',
            [
                new Src\Builder\Config\ValueObject\SubProperty(
                    'identifier',
                    'name',
                    'type',
                ),
            ],
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSubPropertiesReturnsSubProperties(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\SubProperty(
                    'identifier',
                    'name',
                    'type',
                ),
            ],
            $this->subject->getSubProperties(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasSubPropertiesChecksWhetherPropertyHasSubProperties(): void
    {
        self::assertTrue($this->subject->hasSubProperties());

        $subject = new Src\Builder\Config\ValueObject\Property('identifier', 'name');

        self::assertFalse($subject->hasSubProperties());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getIdentifierReturnsIdentifier(): void
    {
        self::assertSame('identifier', $this->subject->getIdentifier());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPathReturnsPath(): void
    {
        self::assertSame('path', $this->subject->getPath());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPathFallsBackToIdentifierIfPathIsNotSet(): void
    {
        $subject = new Src\Builder\Config\ValueObject\Property('identifier', 'name');

        self::assertSame('identifier', $subject->getPath());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getNameReturnsName(): void
    {
        self::assertSame('name', $this->subject->getName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValueReturnsValue(): void
    {
        self::assertSame('value', $this->subject->getValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasValueChecksIfPropertyHasValue(): void
    {
        self::assertTrue($this->subject->hasValue());

        $subject = new Src\Builder\Config\ValueObject\Property('identifier', 'name');

        self::assertFalse($subject->hasValue());
    }
}
