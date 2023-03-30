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

namespace CPSIT\ProjectBuilder\Tests\Builder\Writer;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function basename;
use function dirname;

/**
 * TemplateWriterTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TemplateWriterTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Writer\TemplateWriter $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Writer\TemplateWriter::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function writeWritesRenderedTemplateFileToTemporaryDirectory(): void
    {
        $instructions = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo',
        );
        $instructions->addTemplateVariable('foo', 'foo');
        $instructions->addTemplateVariable('bar', 'foo');

        $templateFile = dirname(__DIR__, 3).'/templates/dump.json.twig';
        $file = new Finder\SplFileInfo($templateFile, dirname($templateFile), basename($templateFile));

        $expected = $instructions->getTemporaryDirectory().'/dump.json';
        $actual = $this->subject->write($instructions, $file, variables: ['bar' => 'bar']);

        self::assertSame($expected, $actual->getPathname());
        self::assertFileExists($expected);

        $expectedJson = [
            'instructions' => [
                'sourceDirectory' => dirname(__DIR__, 2).'/templates/src',
                'sharedSourceDirectory' => dirname(__DIR__, 2).'/templates/shared',
            ],
            'foo' => 'foo',
            'bar' => 'bar',
        ];

        self::assertJson($actual->getContents());
        self::assertSame($expectedJson, json_decode($actual->getContents(), true, 512, JSON_THROW_ON_ERROR));

        (new Filesystem\Filesystem())->remove(dirname($expected));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function writeWritesRenderedTemplateFileToGivenTargetFile(): void
    {
        $instructions = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo',
        );
        $instructions->addTemplateVariable('foo', 'foo');
        $instructions->addTemplateVariable('bar', 'foo');

        $templateFile = dirname(__DIR__, 3).'/templates/dump.json.twig';
        $file = new Finder\SplFileInfo($templateFile, dirname($templateFile), basename($templateFile));

        $expected = $instructions->getTemporaryDirectory().'/overrides/dump.json';
        $actual = $this->subject->write($instructions, $file, 'overrides/dump.json', ['bar' => 'bar']);

        self::assertSame($expected, $actual->getPathname());
        self::assertFileExists($expected);

        $expectedJson = [
            'instructions' => [
                'sourceDirectory' => dirname(__DIR__, 2).'/templates/src',
                'sharedSourceDirectory' => dirname(__DIR__, 2).'/templates/shared',
            ],
            'foo' => 'foo',
            'bar' => 'bar',
        ];

        self::assertJson($actual->getContents());
        self::assertSame($expectedJson, json_decode($actual->getContents(), true, 512, JSON_THROW_ON_ERROR));

        (new Filesystem\Filesystem())->remove(dirname($expected));
    }
}
