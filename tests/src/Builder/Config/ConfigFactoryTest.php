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

namespace CPSIT\ProjectBuilder\Tests\Builder\Config;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework\TestCase;

use function dirname;
use function ucfirst;

/**
 * ConfigFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigFactoryTest extends TestCase
{
    private Src\Builder\Config\ConfigFactory $subject;

    protected function setUp(): void
    {
        $this->subject = Src\Builder\Config\ConfigFactory::create();
    }

    /**
     * @test
     */
    public function buildFromFileThrowsExceptionIfFileTypeIsNotSupported(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "php" is not supported.');

        $this->subject->buildFromFile(__FILE__);
    }

    /**
     * @test
     */
    public function buildFromFileThrowsExceptionIfFileContentsAreInvalid(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1653303396);

        $this->subject->buildFromFile(dirname(__DIR__, 2).'/Fixtures/Files/invalid-config.yaml');
    }

    /**
     * @test
     */
    public function buildFromFileReturnsHydratedConfigObject(): void
    {
        $createConfig = fn (string $type): Src\Builder\Config\Config => new Src\Builder\Config\Config(
            $type,
            ucfirst($type),
            [
                new Src\Builder\Config\ValueObject\Step('collectBuildInstructions'),
                new Src\Builder\Config\ValueObject\Step(
                    'processSourceFiles',
                    new Src\Builder\Config\ValueObject\StepOptions([
                        new Src\Builder\Config\ValueObject\FileCondition('dummy-2.'.$type, 'false'),
                    ])
                ),
                new Src\Builder\Config\ValueObject\Step(
                    'processSharedSourceFiles',
                    new Src\Builder\Config\ValueObject\StepOptions([
                        new Src\Builder\Config\ValueObject\FileCondition('shared-dummy-2.'.$type, 'false'),
                    ])
                ),
                new Src\Builder\Config\ValueObject\Step('mirrorProcessedFiles'),
            ],
            [
                new Src\Builder\Config\ValueObject\Property(
                    'foo',
                    'Foo',
                    null,
                    'foo'
                ),
                new Src\Builder\Config\ValueObject\Property(
                    'bar',
                    'Bar',
                    null,
                    null,
                    [
                        new Src\Builder\Config\ValueObject\SubProperty(
                            'name',
                            'Name',
                            'staticValue',
                            null,
                            null,
                            null,
                            [],
                            false,
                            null,
                            [
                                new Src\Builder\Config\ValueObject\PropertyValidator(
                                    'notEmpty',
                                    [
                                        'strict' => true,
                                    ]
                                ),
                            ],
                        ),
                    ],
                ),
            ],
        );

        foreach (['json', 'yaml'] as $fileType) {
            $configFile = dirname(__DIR__, 2).'/Fixtures/Templates/'.$fileType.'-template/config.'.$fileType;
            $expected = $createConfig($fileType);
            $expected->setDeclaringFile($configFile);

            self::assertEquals($expected, $this->subject->buildFromFile($configFile));
        }
    }

    /**
     * @test
     */
    public function buildFromStringThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "php" is not supported.');

        $this->subject->buildFromString('foo', 'php');
    }

    /**
     * @test
     */
    public function buildFromStringThrowsExceptionIfGivenContentIsInvalid(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1653058480);
        $this->expectExceptionMessage('The config source "foo" is invalid and cannot be processed.');

        $this->subject->buildFromString('foo', 'yaml');
    }
}
