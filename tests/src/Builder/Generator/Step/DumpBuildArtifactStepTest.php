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
use Symfony\Component\Finder;

use function dirname;
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
    private Src\Builder\ArtifactGenerator $artifactGenerator;
    private Src\Builder\BuildResult $buildResult;
    private Finder\SplFileInfo $artifactFile;
    private Package\RootPackageInterface $rootPackage;

    protected function setUp(): void
    {
        $this->filesystem = self::$container->get(Filesystem\Filesystem::class);
        $this->subject = new Src\Builder\Generator\Step\DumpBuildArtifactStep(
            $this->filesystem,
            self::$container->get(Src\Builder\Writer\JsonFileWriter::class),
        );
        $this->artifactGenerator = self::$container->get(Src\Builder\ArtifactGenerator::class);
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
            $this->artifactGenerator,
        );
        $this->artifactFile = Src\Helper\FilesystemHelper::createFileObject(
            $this->buildResult->getWrittenDirectory(),
            '.build/build-artifact.json',
        );
        $this->rootPackage = Src\Resource\Local\Composer::createComposer(dirname(__DIR__, 5))->getPackage();
    }

    /**
     * @test
     */
    public function runDoesNothingIfArtifactWasNotGenerated(): void
    {
        self::assertTrue($this->subject->run($this->buildResult));
        self::assertFalse($this->buildResult->isStepApplied($this->subject));
        self::assertFileDoesNotExist($this->artifactFile->getPathname());
    }

    /**
     * @test
     */
    public function runDumpsArtifact(): void
    {
        $this->buildResult->setArtifactFile($this->artifactFile);

        self::assertTrue($this->subject->run($this->buildResult));
        self::assertTrue($this->buildResult->isStepApplied($this->subject));
        self::assertFileExists($this->artifactFile->getPathname());

        $artifact = $this->artifactGenerator->build($this->artifactFile, $this->buildResult, $this->rootPackage);

        self::assertJsonStringEqualsJsonFile(
            $this->artifactFile->getPathname(),
            json_encode($artifact, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @test
     */
    public function revertDoesNothingIfArtifactWasNotGenerated(): void
    {
        $artifactPath = $this->artifactFile->getPathname();

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
    public function revertRemovesDumpedArtifact(): void
    {
        $artifactPath = $this->artifactFile->getPathname();

        $this->buildResult->setArtifactFile($this->artifactFile);

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

        $this->filesystem->remove($this->artifactFile->getPathname());
    }
}
