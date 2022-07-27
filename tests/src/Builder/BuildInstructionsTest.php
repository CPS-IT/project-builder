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

namespace CPSIT\ProjectBuilder\Tests\Builder;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;

use function dirname;
use function sys_get_temp_dir;

/**
 * BuildInstructionsTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildInstructionsTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\BuildInstructions $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo'
        );
    }

    /**
     * @test
     */
    public function getConfigReturnsConfig(): void
    {
        self::assertSame(self::$container->get('app.config'), $this->subject->getConfig());
    }

    /**
     * @test
     */
    public function getTemplateDirectoryReturnsTemplateDirectory(): void
    {
        self::assertSame(dirname(__DIR__), $this->subject->getTemplateDirectory());
    }

    /**
     * @test
     */
    public function getSourceDirectoryReturnsSourceDirectory(): void
    {
        self::assertSame(dirname(__DIR__).'/templates/src', $this->subject->getSourceDirectory());
    }

    /**
     * @test
     */
    public function getSharedSourceDirectoryReturnsSharedSourceDirectory(): void
    {
        self::assertSame(dirname(__DIR__).'/templates/shared', $this->subject->getSharedSourceDirectory());
    }

    /**
     * @test
     */
    public function getTemporaryDirectoryReturnsUniqueTemporaryDirectory(): void
    {
        $actual = $this->subject->getTemporaryDirectory();

        self::assertDirectoryDoesNotExist($actual);
        self::assertStringStartsWith(sys_get_temp_dir(), $actual);
    }

    /**
     * @test
     */
    public function getTargetDirectoryReturnsTargetDirectory(): void
    {
        self::assertSame('foo', $this->subject->getTargetDirectory());
    }

    /**
     * @test
     */
    public function getTemplateVariablesReturnsTemplateVariables(): void
    {
        self::assertSame([], $this->subject->getTemplateVariables());

        $this->subject['foo'] = 'bar';

        self::assertSame(['foo' => 'bar'], $this->subject->getTemplateVariables());
    }

    /**
     * @test
     */
    public function getTemplateVariableReturnsTemplateVariableAtGivenPath(): void
    {
        self::assertNull($this->subject->getTemplateVariable('foo.bar.hello'));

        $this->subject['foo'] = [
            'bar' => [
                'hello' => 'world!',
            ],
        ];

        self::assertSame('world!', $this->subject->getTemplateVariable('foo.bar.hello'));
    }

    /**
     * @test
     */
    public function addTemplateVariableSetsTemplateVariableAtGivenPath(): void
    {
        self::assertNull($this->subject->getTemplateVariable('foo.bar.hello'));

        $this->subject->addTemplateVariable('foo.bar.hello', 'world!');

        self::assertSame('world!', $this->subject->getTemplateVariable('foo.bar.hello'));
    }
}
