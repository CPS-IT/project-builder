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
use Generator;
use Symfony\Component\Filesystem;

/**
 * GenerateBuildArtifactStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GenerateBuildArtifactStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\GenerateBuildArtifactStep $subject;
    private Filesystem\Filesystem $filesystem;
    private Src\Builder\BuildResult $buildResult;
    private string $artifactPath;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\GenerateBuildArtifactStep::class);
        $this->filesystem = self::$container->get(Filesystem\Filesystem::class);
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
        );
        $this->artifactPath = Filesystem\Path::join(
            $this->buildResult->getWrittenDirectory(),
            '.build/build-artifact.json',
        );
    }

    /**
     * @test
     *
     * @dataProvider runAsksForConfirmationIfBuildArtifactPathAlreadyExistsDataProvider
     */
    public function runAsksForConfirmationIfBuildArtifactPathAlreadyExists(bool $continue, bool $expected): void
    {
        self::$io->setUserInputs([$continue ? 'yes' : 'no']);

        $this->filesystem->dumpFile($this->artifactPath, 'test');

        self::assertFileExists($this->artifactPath);
        self::assertSame($expected, $this->subject->run($this->buildResult));
        self::assertSame(!$expected, $this->subject->isStopped());
        self::assertFalse($this->buildResult->isStepApplied($this->subject));
        self::assertNull($this->buildResult->getBuildArtifact());
        self::assertStringContainsString(
            'The build artifact cannot be generated because the resulting file already exists.',
            self::$io->getOutput(),
        );
    }

    /**
     * @test
     */
    public function runGeneratesBuildArtifact(): void
    {
        self::assertTrue($this->subject->run($this->buildResult));
        self::assertInstanceOf(Src\Builder\BuildArtifact::class, $this->buildResult->getBuildArtifact());
        self::assertTrue($this->buildResult->isStepApplied($this->subject));
    }

    /**
     * @return Generator<string, array{bool, bool}>
     */
    public function runAsksForConfirmationIfBuildArtifactPathAlreadyExistsDataProvider(): Generator
    {
        yield 'continue' => [true, true];
        yield 'do not continue' => [false, false];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->artifactPath);
    }
}
