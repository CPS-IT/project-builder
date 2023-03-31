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

namespace CPSIT\ProjectBuilder\Tests\Builder;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

use function dirname;

/**
 * ArtifactReaderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArtifactReaderTest extends Tests\ContainerAwareTestCase
{
    private string $artifactFile;
    private Tests\Fixtures\DummyVersion $version;
    private Src\Builder\ArtifactReader $subject;

    protected function setUp(): void
    {
        $this->artifactFile = dirname(__DIR__).'/Fixtures/Files/build-artifact.json';
        $this->version = self::$container->get(Tests\Fixtures\DummyVersion::class);
        $this->subject = self::$container->get(Src\Builder\ArtifactReader::class);
    }

    #[Framework\Attributes\Test]
    public function fromFileThrowsExceptionIfFileIsNotReadable(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidArtifactException::forFile('foo'));

        $this->subject->fromFile('foo');
    }

    #[Framework\Attributes\Test]
    public function fromFileThrowsExceptionIfJsonIsInvalid(): void
    {
        $file = dirname(__DIR__).'/Fixtures/Files/invalid-json.json';

        $this->expectExceptionObject(Src\Exception\InvalidArtifactException::forFile($file));

        $this->subject->fromFile($file);
    }

    #[Framework\Attributes\Test]
    public function fromFileThrowsExceptionIfJsonIsUnsupported(): void
    {
        $file = dirname(__DIR__).'/Fixtures/Files/invalid-artifact.json';

        $this->expectExceptionObject(Src\Exception\InvalidArtifactException::forFile($file));

        $this->subject->fromFile($file);
    }

    #[Framework\Attributes\Test]
    public function fromFileThrowsExceptionIfArtifactVersionIsInvalid(): void
    {
        $file = dirname(__DIR__).'/Fixtures/Files/invalid-artifact-version.json';

        $this->expectExceptionObject(Src\Exception\InvalidArtifactException::forInvalidVersion());

        $this->subject->fromFile($file);
    }

    #[Framework\Attributes\Test]
    public function fromFileThrowsExceptionIfMigratedArtifactIsInvalid(): void
    {
        $this->version->remapArguments = [
            'artifact',
            null,
            'foo',
        ];

        $this->expectException(Src\Exception\InvalidArtifactException::class);
        $this->expectExceptionCode(1677601857);

        $this->subject->fromFile($this->artifactFile);
    }

    #[Framework\Attributes\Test]
    public function fromFilePerformsMigrations(): void
    {
        $this->version->remapArguments = [
            'generator.executor',
            null,
            'docker',
        ];

        $actual = $this->subject->fromFile($this->artifactFile);

        self::assertSame('docker', $actual->generator->executor);
    }
}
