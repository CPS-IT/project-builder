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
use PHPUnit\Framework;

use function dirname;

/**
 * ConfigReaderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigReaderTest extends Framework\TestCase
{
    private Src\Builder\Config\ConfigReader $subject;

    protected function setUp(): void
    {
        $this->subject = Src\Builder\Config\ConfigReader::create(
            dirname(__DIR__, 2) . '/Fixtures/Templates',
        );
    }

    #[Framework\Attributes\Test]
    public function createCreatesTemplateDirectoryIfItDoesNotExist(): void
    {
        $templateDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        self::assertDirectoryDoesNotExist($templateDirectory);

        Src\Builder\Config\ConfigReader::create($templateDirectory);

        self::assertDirectoryExists($templateDirectory);
    }

    #[Framework\Attributes\Test]
    public function readConfigThrowsExceptionIfTemplateHasNoComposerJson(): void
    {
        $templateDirectory = dirname(__DIR__, 2) . '/Fixtures';
        $subject = Src\Builder\Config\ConfigReader::create($templateDirectory);

        $this->expectExceptionObject(
            Src\Exception\InvalidConfigurationException::forMissingManifestFile($templateDirectory . '/Files/config.json'),
        );

        $subject->readConfig('foo');
    }

    #[Framework\Attributes\Test]
    public function readConfigThrowsExceptionIfTemplateWithGivenIdentifierDoesNotExist(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1652952150);
        $this->expectExceptionMessage('The config for "foo" does not exist or is not valid.');

        $this->subject->readConfig('foo');
    }

    #[Framework\Attributes\Test]
    public function readConfigThrowsExceptionIfTemplateContainsMultipleConfigFiles(): void
    {
        $subject = Src\Builder\Config\ConfigReader::create(
            dirname(__DIR__, 2) . '/Fixtures/Files',
        );

        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1652950002);
        $this->expectExceptionMessageMatches('#^Configuration for "cpsit/project-builder-template-invalid" already exists as "[^"]+"\\. Please use only one config file per template!$#');

        $subject->readConfig('cpsit/project-builder-template-invalid');
    }

    #[Framework\Attributes\Test]
    public function readConfigReturnsHydratedConfigObject(): void
    {
        $actual = $this->subject->readConfig('cpsit/project-builder-template-yaml');

        self::assertSame('cpsit/project-builder-template-yaml', $actual->getIdentifier());
        self::assertSame(
            dirname(__DIR__, 2) . '/Fixtures/Templates/yaml-template/config.yaml',
            $actual->getDeclaringFile(),
        );
    }

    #[Framework\Attributes\Test]
    public function hasConfigChecksWhetherConfigWithGivenIdentifierExists(): void
    {
        self::assertTrue($this->subject->hasConfig('cpsit/project-builder-template-json'));
        self::assertTrue($this->subject->hasConfig('cpsit/project-builder-template-yaml'));
        self::assertFalse($this->subject->hasConfig('foo'));
    }

    #[Framework\Attributes\Test]
    public function listTemplateListsAllAvailableTemplates(): void
    {
        $expected = [
            'cpsit/project-builder-template-json' => 'Json',
            'cpsit/project-builder-template-yaml' => 'Yaml',
        ];

        self::assertSame($expected, $this->subject->listTemplates());
    }
}
