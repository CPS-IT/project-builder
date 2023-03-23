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

/**
 * InteractionFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InteractionFactoryTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\Interaction\InteractionFactory $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\Interaction\InteractionFactory::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getThrowsExceptionIfNoInteractionOfGivenTypeIsAvailable(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "foo" is not supported.');

        $this->subject->get('foo');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getReturnsInteractionOfGivenType(): void
    {
        self::assertInstanceOf(
            Src\Builder\Generator\Step\Interaction\QuestionInteraction::class,
            $this->subject->get('question'),
        );
        self::assertInstanceOf(
            Src\Builder\Generator\Step\Interaction\SelectInteraction::class,
            $this->subject->get('select'),
        );
        self::assertInstanceOf(
            Src\Builder\Generator\Step\Interaction\StaticValueInteraction::class,
            $this->subject->get('staticValue'),
        );
    }
}
