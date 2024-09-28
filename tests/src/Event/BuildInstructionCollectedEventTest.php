<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2024 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Tests\Event;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * BuildInstructionCollectedEventTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildInstructionCollectedEventTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Config\ValueObject\SubProperty $subProperty;
    private Src\Builder\Config\ValueObject\Property $property;
    private Src\Builder\BuildResult $buildResult;
    private Src\Event\BuildInstructionCollectedEvent $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subProperty = new Src\Builder\Config\ValueObject\SubProperty(
            'bar',
            'Bar',
            'staticValue',
            null,
            'false',
        );
        $this->property = new Src\Builder\Config\ValueObject\Property(
            'foo',
            'Foo',
            null,
            null,
            'FOO',
            [
                $this->subProperty,
            ],
        );
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($this->config, 'foo'),
        );
        $this->subject = new Src\Event\BuildInstructionCollectedEvent(
            $this->property,
            'foo',
            'FOO',
            $this->buildResult,
        );
    }

    #[Framework\Attributes\Test]
    public function getPropertyReturnsProperty(): void
    {
        self::assertSame(
            $this->property,
            $this->subject->getProperty(),
        );
    }

    #[Framework\Attributes\Test]
    public function isPropertyReturnsTrueIfGivenPropertyIsAProperty(): void
    {
        self::assertTrue($this->subject->isProperty());
        self::assertFalse($this->subject->isSubProperty());
    }

    #[Framework\Attributes\Test]
    public function isSubPropertyReturnsTrueIfGivenPropertyIsASubProperty(): void
    {
        $subject = new Src\Event\BuildInstructionCollectedEvent(
            $this->subProperty,
            'bar',
            null,
            $this->buildResult,
        );

        self::assertTrue($subject->isSubProperty());
        self::assertFalse($subject->isProperty());
    }

    #[Framework\Attributes\Test]
    public function getPathReturnsPath(): void
    {
        self::assertSame(
            'foo',
            $this->subject->getPath(),
        );
    }

    #[Framework\Attributes\Test]
    public function getValueReturnsValue(): void
    {
        self::assertSame(
            'FOO',
            $this->subject->getValue(),
        );
    }

    #[Framework\Attributes\Test]
    public function setValueAppliesNewValue(): void
    {
        $this->subject->setValue('bar');

        self::assertSame(
            'bar',
            $this->subject->getValue(),
        );
    }

    #[Framework\Attributes\Test]
    public function getBuildResultReturnsBuildResult(): void
    {
        self::assertSame(
            $this->buildResult,
            $this->subject->getBuildResult(),
        );
    }
}
