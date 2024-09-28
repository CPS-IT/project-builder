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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * CollectBuildInstructionsStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CollectBuildInstructionsStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\CollectBuildInstructionsStep $subject;
    private Tests\Fixtures\DummyEventListener $eventListener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Generator\Step\CollectBuildInstructionsStep::class);
        $this->eventListener = $this->container->get(Tests\Fixtures\DummyEventListener::class);
    }

    #[Framework\Attributes\Test]
    public function runAppliesNullAsDefaultValueOnSkippedProperties(): void
    {
        $property = new Src\Builder\Config\ValueObject\Property(
            'foo',
            'Foo',
            null,
            'false',
            'baz',
        );
        $config = new Src\Builder\Config\Config(
            'test',
            'Test',
            [
                new Src\Builder\Config\ValueObject\Step('dummy'),
            ],
            [
                $property,
            ],
        );
        $config->setDeclaringFile(__FILE__);

        $buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($config, 'foo'),
        );

        $this->subject->run($buildResult);

        self::assertNull($buildResult->getInstructions()->getTemplateVariable('foo'));
        self::assertCount(1, $this->eventListener->dispatchedEvents);

        $event = $this->eventListener->dispatchedEvents[0];

        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $event);
        self::assertSame($property, $event->getProperty());
        self::assertNull($event->getValue());
    }

    #[Framework\Attributes\Test]
    public function runAppliesNullAsDefaultValueOnSkippedSubProperties(): void
    {
        $subProperty = new Src\Builder\Config\ValueObject\SubProperty(
            'bar',
            'Bar',
            'staticValue',
            null,
            'false',
        );
        $config = new Src\Builder\Config\Config(
            'test',
            'Test',
            [
                new Src\Builder\Config\ValueObject\Step('dummy'),
            ],
            [
                new Src\Builder\Config\ValueObject\Property(
                    'foo',
                    'Foo',
                    null,
                    null,
                    null,
                    [
                        $subProperty,
                    ],
                ),
            ],
        );
        $config->setDeclaringFile(__FILE__);

        $buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($config, 'foo'),
        );

        $this->subject->run($buildResult);

        self::assertSame(['bar' => null], $buildResult->getInstructions()->getTemplateVariable('foo'));
        self::assertNull($buildResult->getInstructions()->getTemplateVariable('foo/bar'));
        self::assertCount(1, $this->eventListener->dispatchedEvents);

        $event = $this->eventListener->dispatchedEvents[0];

        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $event);
        self::assertSame($subProperty, $event->getProperty());
        self::assertNull($event->getValue());
    }

    #[Framework\Attributes\Test]
    public function runRendersPropertyValueAsTwigTemplate(): void
    {
        $fooProperty = new Src\Builder\Config\ValueObject\Property(
            'foo',
            'Foo',
            null,
            null,
            '{{ "foo"|convert_case("upper") }}',
        );
        $barProperty = new Src\Builder\Config\ValueObject\Property(
            'bar',
            'Bar',
            null,
            null,
            0,
        );
        $config = new Src\Builder\Config\Config(
            'test',
            'Test',
            [
                new Src\Builder\Config\ValueObject\Step('dummy'),
            ],
            [
                $fooProperty,
                $barProperty,
            ],
        );

        $buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($config, 'foo'),
        );

        $this->subject->run($buildResult);

        self::assertSame('FOO', $buildResult->getInstructions()->getTemplateVariable('foo'));
        self::assertSame(0, $buildResult->getInstructions()->getTemplateVariable('bar'));
        self::assertCount(2, $this->eventListener->dispatchedEvents);

        $fooEvent = $this->eventListener->dispatchedEvents[0];
        $barEvent = $this->eventListener->dispatchedEvents[1];

        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $fooEvent);
        self::assertSame($fooProperty, $fooEvent->getProperty());
        self::assertSame('FOO', $fooEvent->getValue());

        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $barEvent);
        self::assertSame($barProperty, $barEvent->getProperty());
        self::assertSame(0, $barEvent->getValue());
    }

    protected function tearDown(): void
    {
        $this->eventListener->dispatchedEvents = [];
    }
}
