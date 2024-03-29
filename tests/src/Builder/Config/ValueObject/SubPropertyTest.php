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
use PHPUnit\Framework;

/**
 * SubPropertyTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SubPropertyTest extends Framework\TestCase
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

    #[Framework\Attributes\Test]
    public function getPathReturnsPath(): void
    {
        self::assertSame('path', $this->subject->getPath());
    }

    #[Framework\Attributes\Test]
    public function getPathConstructsPathFromIdentifierAndParentProperty(): void
    {
        $subject = new Src\Builder\Config\ValueObject\SubProperty(
            'identifier',
            'name',
            'type',
            parent: new Src\Builder\Config\ValueObject\Property('parent-identifier', 'name'),
        );

        self::assertSame('parent-identifier.identifier', $subject->getPath());
    }

    #[Framework\Attributes\Test]
    public function getOptionsReturnsOptions(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\PropertyOption('value'),
            ],
            $this->subject->getOptions(),
        );
    }

    #[Framework\Attributes\Test]
    public function canHaveMultipleValuesReturnsTrue(): void
    {
        self::assertTrue($this->subject->canHaveMultipleValues());
    }

    #[Framework\Attributes\Test]
    public function getDefaultValueReturnsDefaultValue(): void
    {
        self::assertSame('defaultValue', $this->subject->getDefaultValue());
    }

    #[Framework\Attributes\Test]
    public function getValidatorsReturnsValidators(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\PropertyValidator('type'),
            ],
            $this->subject->getValidators(),
        );
    }

    #[Framework\Attributes\Test]
    public function isRequiredChecksIfNotEmptyValidatorIsSet(): void
    {
        self::assertFalse($this->subject->isRequired());

        $subject = new Src\Builder\Config\ValueObject\SubProperty(
            'identifier',
            'name',
            'type',
            validators: [
                new Src\Builder\Config\ValueObject\PropertyValidator('notEmpty'),
            ],
        );

        self::assertTrue($subject->isRequired());
    }

    #[Framework\Attributes\Test]
    public function getParentReturnsParentProperty(): void
    {
        self::assertEquals(
            new Src\Builder\Config\ValueObject\Property('parent-identifier', 'name'),
            $this->subject->getParent(),
        );
    }

    #[Framework\Attributes\Test]
    public function setParentAppliesParentProperty(): void
    {
        $newParent = new Src\Builder\Config\ValueObject\Property('new-parent', 'name');

        self::assertSame($newParent, $this->subject->setParent($newParent)->getParent());
    }
}
