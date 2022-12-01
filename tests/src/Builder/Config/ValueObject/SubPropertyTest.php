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
use ReflectionObject;

/**
 * SubPropertyTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SubPropertyTest extends TestCase
{
    private Src\Builder\Config\ValueObject\SubProperty $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Config\ValueObject\SubProperty(
            'identifier',
            'name',
            'type',
            'path',
            'if',
            'value',
            [
                new Src\Builder\Config\ValueObject\PropertyOption('value'),
            ],
            true,
            'defaultValue',
            [
                new Src\Builder\Config\ValueObject\PropertyValidator('type'),
            ],
            new Src\Builder\Config\ValueObject\Property('parent-identifier', 'name'),
        );
    }

    /**
     * @test
     */
    public function getPathReturnsPath(): void
    {
        self::assertSame('path', $this->subject->getPath());
    }

    /**
     * @test
     */
    public function getPathConstructsPathFromIdentifierAndParentProperty(): void
    {
        $this->modifySubject('path', null);

        self::assertSame('parent-identifier.identifier', $this->subject->getPath());
    }

    /**
     * @test
     */
    public function getOptionsReturnsOptions(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\PropertyOption('value'),
            ],
            $this->subject->getOptions(),
        );
    }

    /**
     * @test
     */
    public function canHaveMultipleValuesReturnsTrue(): void
    {
        self::assertTrue($this->subject->canHaveMultipleValues());
    }

    /**
     * @test
     */
    public function getDefaultValueReturnsDefaultValue(): void
    {
        self::assertSame('defaultValue', $this->subject->getDefaultValue());
    }

    /**
     * @test
     */
    public function getValidatorsReturnsValidators(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\PropertyValidator('type'),
            ],
            $this->subject->getValidators(),
        );
    }

    /**
     * @test
     */
    public function isRequiredChecksIfNotEmptyValidatorIsSet(): void
    {
        self::assertFalse($this->subject->isRequired());

        $this->modifySubject('validators', [
            new Src\Builder\Config\ValueObject\PropertyValidator('notEmpty'),
        ]);

        self::assertTrue($this->subject->isRequired());
    }

    /**
     * @test
     */
    public function getParentReturnsParentProperty(): void
    {
        self::assertEquals(
            new Src\Builder\Config\ValueObject\Property('parent-identifier', 'name'),
            $this->subject->getParent(),
        );
    }

    /**
     * @test
     */
    public function setParentAppliesParentProperty(): void
    {
        $newParent = new Src\Builder\Config\ValueObject\Property('new-parent', 'name');

        self::assertSame($newParent, $this->subject->setParent($newParent)->getParent());
    }

    private function modifySubject(string $property, mixed $value): void
    {
        $reflection = new ReflectionObject($this->subject);

        if (!$reflection->hasProperty($property)) {
            throw Src\Exception\ShouldNotHappenException::create();
        }

        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->subject, $value);
    }
}
