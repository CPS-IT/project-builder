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
use Generator;
use PHPUnit\Framework;

use function dirname;
use function ucfirst;

/**
 * ConfigFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigFactoryTest extends Framework\TestCase
{
    private Src\Builder\Config\ConfigFactory $subject;

    protected function setUp(): void
    {
        $this->subject = Src\Builder\Config\ConfigFactory::create();
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('buildFromFileThrowsExceptionIfFileContentsAreInvalidDataProvider')]
    public function buildFromFileThrowsExceptionIfFileContentsAreInvalid(string $file): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1653303396);

        $this->subject->buildFromFile($file, 'foo');
    }

    #[Framework\Attributes\Test]
    public function buildFromFileReturnsHydratedConfigObject(): void
    {
        $createConfig = fn(string $type): Src\Builder\Config\Config => new Src\Builder\Config\Config(
            $type,
            ucfirst($type),
            [
                new Src\Builder\Config\ValueObject\Step('collectBuildInstructions'),
                new Src\Builder\Config\ValueObject\Step(
                    'processSourceFiles',
                    new Src\Builder\Config\ValueObject\StepOptions([
                        new Src\Builder\Config\ValueObject\FileCondition('dummy-2.' . $type, 'false'),
                        new Src\Builder\Config\ValueObject\FileCondition('*-3.' . $type, 'false'),
                        new Src\Builder\Config\ValueObject\FileCondition('dummy-4.' . $type, 'false'),
                        new Src\Builder\Config\ValueObject\FileCondition('dummy-4.' . $type, 'true', 'overrides/dummy-4.' . $type),
                        new Src\Builder\Config\ValueObject\FileCondition('dummy/*', null, 'foo-{{ foo }}-dummy/*'),
                    ]),
                ),
                new Src\Builder\Config\ValueObject\Step(
                    'processSharedSourceFiles',
                    new Src\Builder\Config\ValueObject\StepOptions([
                        new Src\Builder\Config\ValueObject\FileCondition('shared-dummy-2.' . $type, 'false'),
                        new Src\Builder\Config\ValueObject\FileCondition('shared-*-3.' . $type, 'false'),
                        new Src\Builder\Config\ValueObject\FileCondition('shared-dummy-4.' . $type, 'true', 'overrides/shared-dummy-4.' . $type),
                        new Src\Builder\Config\ValueObject\FileCondition('shared-dummy/*', null, 'foo-{{ foo }}-shared-dummy/*'),
                    ]),
                ),
                new Src\Builder\Config\ValueObject\Step(
                    'generateBuildArtifact',
                    new Src\Builder\Config\ValueObject\StepOptions(artifactPath: 'foo.json'),
                ),
                new Src\Builder\Config\ValueObject\Step('mirrorProcessedFiles'),
                new Src\Builder\Config\ValueObject\Step(
                    'runCommand',
                    new Src\Builder\Config\ValueObject\StepOptions(command: 'echo \'foo\''),
                ),
                new Src\Builder\Config\ValueObject\Step(
                    'runCommand',
                    new Src\Builder\Config\ValueObject\StepOptions(command: 'echo \'baz\'', skipConfirmation: true),
                ),
            ],
            [
                new Src\Builder\Config\ValueObject\Property(
                    'foo',
                    'Foo',
                    null,
                    'false',
                    'foo',
                ),
                new Src\Builder\Config\ValueObject\Property(
                    'bar',
                    'Bar',
                    null,
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
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
            ],
        );

        foreach (['json', 'yaml'] as $fileType) {
            $configFile = dirname(__DIR__, 2) . '/Fixtures/Templates/' . $fileType . '-template/config.' . $fileType;
            $expected = $createConfig($fileType);
            $expected->setDeclaringFile($configFile);

            self::assertEquals($expected, $this->subject->buildFromFile($configFile, $fileType));
        }
    }

    #[Framework\Attributes\Test]
    public function buildFromStringThrowsExceptionIfGivenContentIsInvalid(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1653058480);
        $this->expectExceptionMessage('The config source "foo" is invalid and cannot be processed.');

        $this->subject->buildFromString('foo', 'baz', Src\Builder\Config\FileType::Yaml);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function buildFromFileThrowsExceptionIfFileContentsAreInvalidDataProvider(): Generator
    {
        $fixturePath = dirname(__DIR__, 2) . '/Fixtures/Files';

        yield 'invalid file' => [$fixturePath . '/invalid-config-file.yaml'];
        yield 'invalid path at file condition' => [$fixturePath . '/invalid-config-file-condition-path.yaml'];
        yield 'invalid target at file condition' => [$fixturePath . '/invalid-config-file-condition-target.yaml'];
    }
}
