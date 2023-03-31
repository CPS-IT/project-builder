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

namespace CPSIT\ProjectBuilder\Tests\Event;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * BuildStepProcessedEventTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildStepProcessedEventTest extends Tests\ContainerAwareTestCase
{
    private Tests\Fixtures\DummyStep $step;
    private Src\Builder\BuildResult $buildResult;
    private Src\Event\BuildStepProcessedEvent $subject;

    protected function setUp(): void
    {
        $this->step = new Tests\Fixtures\DummyStep();
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
        );
        $this->subject = new Src\Event\BuildStepProcessedEvent(
            $this->step,
            $this->buildResult,
            false,
        );
    }

    #[Framework\Attributes\Test]
    public function getStepReturnsStep(): void
    {
        self::assertSame(
            $this->step,
            $this->subject->getStep(),
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

    #[Framework\Attributes\Test]
    public function isSuccessfulReturnsState(): void
    {
        self::assertFalse($this->subject->isSuccessful());
    }
}
