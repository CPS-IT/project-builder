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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;

/**
 * StepFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StepFactoryTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\StepFactory $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\StepFactory::class);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfGivenStepIsNotSupported(): void
    {
        $step = new Src\Builder\Config\ValueObject\Step('foo');

        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "foo" is not supported.');

        $this->subject->get($step);
    }

    /**
     * @test
     */
    public function getReturnsStepForGivenStep(): void
    {
        $step = new Src\Builder\Config\ValueObject\Step('collectBuildInstructions');

        self::assertInstanceOf(
            Src\Builder\Generator\Step\CollectBuildInstructionsStep::class,
            $this->subject->get($step)
        );
    }
}
