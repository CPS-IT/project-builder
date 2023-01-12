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

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Symfony\Component\Filesystem;

use function json_encode;

/**
 * DumpBuildArtifactStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DumpBuildArtifactStepTest extends Tests\ContainerAwareTestCase
{
    private Filesystem\Filesystem $filesystem;
    private Src\Builder\Generator\Step\DumpBuildArtifactStep $subject;
    private Src\Builder\BuildResult $buildResult;
    private Src\Builder\BuildArtifact $buildArtifact;

    protected function setUp(): void
    {
        $this->filesystem = self::$container->get(Filesystem\Filesystem::class);
        $this->subject = new Src\Builder\Generator\Step\DumpBuildArtifactStep(
            $this->filesystem,
            self::$container->get(Src\Builder\Writer\JsonFileWriter::class),
        );
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
        );
        $this->buildArtifact = new Src\Builder\BuildArtifact(
            Src\Helper\FilesystemHelper::createFileObject(
                Src\Helper\FilesystemHelper::getNewTemporaryDirectory(),
                'foo.json',
            ),
            $this->buildResult,
            new Package\RootPackage('foo/baz', '1.0.0', '1.0.0'),
        );
    }

    /**
     * @test
     */
    public function runDoesNothingIfBuildArtifactWasNotGenerated(): void
    {
        self::assertTrue($this->subject->run($this->buildResult));
        self::assertFalse($this->buildResult->isStepApplied($this->subject));
        self::assertFileDoesNotExist($this->buildArtifact->getPath()->getPathname());
    }

    /**
     * @test
     */
    public function runDumpsBuildArtifact(): void
    {
        $this->buildResult->setBuildArtifact($this->buildArtifact);

        self::assertTrue($this->subject->run($this->buildResult));
        self::assertTrue($this->buildResult->isStepApplied($this->subject));
        self::assertFileExists($this->buildArtifact->getPath()->getPathname());
        self::assertJsonStringEqualsJsonFile(
            $this->buildArtifact->getPath()->getPathname(),
            json_encode($this->buildArtifact, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @test
     */
    public function revertDoesNothingIfBuildArtifactWasNotGenerated(): void
    {
        $artifactPath = $this->buildArtifact->getPath()->getPathname();

        $this->filesystem->dumpFile($artifactPath, 'test');

        self::assertFileExists($artifactPath);
        self::assertStringEqualsFile($artifactPath, 'test');

        $this->subject->revert($this->buildResult);

        self::assertFileExists($artifactPath);
        self::assertStringEqualsFile($artifactPath, 'test');
    }

    /**
     * @test
     */
    public function revertRemovesDumpedBuildArtifact(): void
    {
        $artifactPath = $this->buildArtifact->getPath()->getPathname();

        $this->buildResult->setBuildArtifact($this->buildArtifact);

        self::assertFileDoesNotExist($artifactPath);

        $this->subject->run($this->buildResult);

        self::assertFileExists($artifactPath);

        $this->subject->revert($this->buildResult);

        self::assertFileDoesNotExist($artifactPath);
    }

    /**
     * @test
     */
    public function supportsReturnsFalse(): void
    {
        self::assertFalse($this->subject::supports('foo'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->buildArtifact->getPath()->getPathname());
    }
}
