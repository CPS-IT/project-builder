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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step\Interaction;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * SelectInteractionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SelectInteractionTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\Interaction\SelectInteraction $subject;
    private Src\Builder\BuildInstructions $instructions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Generator\Step\Interaction\SelectInteraction::class);
        $this->instructions = new Src\Builder\BuildInstructions($this->config, 'foo');
    }

    #[Framework\Attributes\Test]
    public function interactReturnsNullOnEmptyUserInput(): void
    {
        $interactionSubject = $this->buildInteractionSubject();

        self::assertNull($this->subject->interact($interactionSubject, $this->instructions));
    }

    #[Framework\Attributes\Test]
    public function interactReturnsFirstOptionOnEmptyUserInputAndRequiredSelection(): void
    {
        $propertyOptions = [
            new Src\Builder\Config\ValueObject\PropertyOption('foo'),
            new Src\Builder\Config\ValueObject\PropertyOption('bar'),
        ];
        $interactionSubject = $this->buildInteractionSubject($propertyOptions, false, null, true);

        self::assertSame('foo', $this->subject->interact($interactionSubject, $this->instructions));
    }

    #[Framework\Attributes\Test]
    public function interactReturnsDefaultValueOnEmptyUserInputAndRequiredSelection(): void
    {
        $propertyOptions = [
            new Src\Builder\Config\ValueObject\PropertyOption('foo'),
            new Src\Builder\Config\ValueObject\PropertyOption('bar'),
        ];
        $interactionSubject = $this->buildInteractionSubject($propertyOptions, false, 'bar', true);

        self::assertSame('bar', $this->subject->interact($interactionSubject, $this->instructions));
    }

    #[Framework\Attributes\Test]
    public function interactReturnsSelectedOption(): void
    {
        $propertyOptions = [
            new Src\Builder\Config\ValueObject\PropertyOption('foo'),
            new Src\Builder\Config\ValueObject\PropertyOption('bar'),
        ];
        $interactionSubject = $this->buildInteractionSubject($propertyOptions, false, null, true);

        $this->io->setUserInputs(['bar']);

        self::assertSame('bar', $this->subject->interact($interactionSubject, $this->instructions));
    }

    #[Framework\Attributes\Test]
    public function interactReturnsSelectedOptionsIfMultipleOptionsAreAllowed(): void
    {
        $propertyOptions = [
            new Src\Builder\Config\ValueObject\PropertyOption('foo'),
            new Src\Builder\Config\ValueObject\PropertyOption('bar'),
            new Src\Builder\Config\ValueObject\PropertyOption('hello'),
            new Src\Builder\Config\ValueObject\PropertyOption('world'),
        ];
        $interactionSubject = $this->buildInteractionSubject($propertyOptions, true);

        $this->io->setUserInputs(['hello,bar']);

        self::assertSame(['bar', 'hello'], $this->subject->interact($interactionSubject, $this->instructions));
    }

    /**
     * @param list<Src\Builder\Config\ValueObject\PropertyOption> $options
     */
    private function buildInteractionSubject(
        array $options = [],
        bool $multiple = false,
        int|float|string|bool|null $defaultValue = null,
        bool $required = false,
    ): Src\Builder\Config\ValueObject\CustomizableInterface {
        $validators = [];

        if ($required) {
            $validators[] = new Src\Builder\Config\ValueObject\PropertyValidator('notEmpty');
        }

        return new Src\Builder\Config\ValueObject\SubProperty(
            'foo',
            'foo',
            'foo',
            null,
            null,
            null,
            $options,
            $multiple,
            $defaultValue,
            $validators,
        );
    }
}
