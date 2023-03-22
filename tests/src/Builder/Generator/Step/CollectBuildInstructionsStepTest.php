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

/**
 * CollectBuildInstructionsStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CollectBuildInstructionsStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\CollectBuildInstructionsStep $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\CollectBuildInstructionsStep::class);
    }

    /**
     * @test
     */
    public function runAppliesNullAsDefaultValueOnSkippedProperties(): void
    {
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
                    'false',
                    'baz',
                ),
            ],
        );
        $config->setDeclaringFile(__FILE__);

        $buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($config, 'foo'),
            self::$container->get(Src\Builder\ArtifactGenerator::class),
        );

        $this->subject->run($buildResult);

        self::assertNull($buildResult->getInstructions()->getTemplateVariable('foo'));
    }

    /**
     * @test
     */
    public function runAppliesNullAsDefaultValueOnSkippedSubProperties(): void
    {
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
                        new Src\Builder\Config\ValueObject\SubProperty(
                            'bar',
                            'Bar',
                            'staticValue',
                            null,
                            'false',
                        ),
                    ],
                ),
            ],
        );
        $config->setDeclaringFile(__FILE__);

        $buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($config, 'foo'),
            self::$container->get(Src\Builder\ArtifactGenerator::class),
        );

        $this->subject->run($buildResult);

        self::assertSame(['bar' => null], $buildResult->getInstructions()->getTemplateVariable('foo'));
        self::assertNull($buildResult->getInstructions()->getTemplateVariable('foo/bar'));
    }
}
