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

/**
 * BuildStepRevertedEventTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildStepRevertedEventTest extends Tests\ContainerAwareTestCase
{
    private Tests\Fixtures\DummyStep $step;
    private Src\Builder\BuildResult $buildResult;
    private Src\Event\BuildStepRevertedEvent $subject;

    protected function setUp(): void
    {
        $this->step = new Tests\Fixtures\DummyStep();
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
            self::$container->get(Src\Builder\ArtifactGenerator::class),
        );
        $this->subject = new Src\Event\BuildStepRevertedEvent(
            $this->step,
            $this->buildResult,
        );
    }

    /**
     * @test
     */
    public function getStepReturnsStep(): void
    {
        self::assertSame(
            $this->step,
            $this->subject->getStep(),
        );
    }

    /**
     * @test
     */
    public function getBuildResultReturnsBuildResult(): void
    {
        self::assertSame(
            $this->buildResult,
            $this->subject->getBuildResult(),
        );
    }
}
