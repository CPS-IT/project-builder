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
use Twig\Environment;

/**
 * BeforeTemplateRenderedEventTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BeforeTemplateRenderedEventTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\BuildInstructions $buildInstructions;
    private Src\Event\BeforeTemplateRenderedEvent $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildInstructions = new Src\Builder\BuildInstructions($this->config, 'foo');
        $this->subject = new Src\Event\BeforeTemplateRenderedEvent(
            $this->container->get(Environment::class),
            $this->buildInstructions,
            [
                'foo' => true,
                'baz' => false,
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function getTwigReturnsTwigEnvironment(): void
    {
        self::assertSame(
            $this->container->get(Environment::class),
            $this->subject->getTwig(),
        );
    }

    #[Framework\Attributes\Test]
    public function getInstructionsReturnsBuildInstructions(): void
    {
        self::assertSame(
            $this->buildInstructions,
            $this->subject->getInstructions(),
        );
    }

    #[Framework\Attributes\Test]
    public function getVariablesReturnsVariables(): void
    {
        self::assertSame(
            [
                'foo' => true,
                'baz' => false,
            ],
            $this->subject->getVariables(),
        );
    }

    #[Framework\Attributes\Test]
    public function setVariablesUpdatesVariables(): void
    {
        $this->subject->setVariables(['dummy' => null]);

        self::assertSame(['dummy' => null], $this->subject->getVariables());
    }
}
