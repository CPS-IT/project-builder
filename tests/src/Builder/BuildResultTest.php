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
use Symfony\Component\Finder;

use function basename;
use function dirname;

/**
 * BuildResultTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildResultTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\BuildInstructions $instructions;
    private Src\Builder\BuildResult $subject;

    protected function setUp(): void
    {
        $this->instructions = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo',
        );
        $this->subject = new Src\Builder\BuildResult(
            $this->instructions,
            self::$container->get(Src\Builder\ArtifactGenerator::class),
        );
    }

    /**
     * @test
     */
    public function getInstructionsReturnsInstructions(): void
    {
        self::assertSame($this->instructions, $this->subject->getInstructions());
    }

    /**
     * @test
     */
    public function isMirroredReturnsMirrorState(): void
    {
        self::assertFalse($this->subject->isMirrored());
        self::assertTrue($this->subject->setMirrored(true)->isMirrored());
    }

    /**
     * @test
     */
    public function getArtifactFileReturnsArtifactFile(): void
    {
        self::assertNull($this->subject->getArtifactFile());

        $artifactFile = Src\Helper\FilesystemHelper::createFileObject('/foo', 'baz');

        self::assertSame($artifactFile, $this->subject->setArtifactFile($artifactFile)->getArtifactFile());
    }

    /**
     * @test
     */
    public function getArtifactReturnsArtifact(): void
    {
        self::assertNull($this->subject->getArtifact());

        $artifactFile = Src\Helper\FilesystemHelper::createFileObject('/foo', 'baz');

        $this->subject->setArtifactFile($artifactFile);

        self::assertInstanceOf(Src\Builder\Artifact\Artifact::class, $this->subject->getArtifact());
    }

    /**
     * @test
     */
    public function getAppliedStepsReturnsAppliedSteps(): void
    {
        $step = new Tests\Fixtures\DummyStep();

        self::assertSame([], $this->subject->getAppliedSteps());

        $this->subject->applyStep($step);

        self::assertSame([$step::getType() => $step], $this->subject->getAppliedSteps());
    }

    /**
     * @test
     */
    public function isStepAppliedTestsWhetherStepIsApplied(): void
    {
        $step = new Tests\Fixtures\DummyStep();

        $this->subject->applyStep($step);

        self::assertTrue($this->subject->isStepApplied($step));
        self::assertTrue($this->subject->isStepApplied($step::getType()));
        self::assertTrue($this->subject->isStepApplied(clone $step));

        self::assertFalse($this->subject->isStepApplied('foo'));
        self::assertFalse($this->subject->isStepApplied(
            self::$container->get(Src\Builder\Generator\Step\InstallComposerDependenciesStep::class),
        ));
    }

    /**
     * @test
     */
    public function applyStepAddsStepToAppliedSteps(): void
    {
        $step = new Tests\Fixtures\DummyStep();

        self::assertFalse($this->subject->isStepApplied($step));

        $this->subject->applyStep($step);

        self::assertTrue($this->subject->isStepApplied($step));
    }

    /**
     * @test
     */
    public function getProcessedFilesReturnsProcessedFiles(): void
    {
        self::assertSame([], $this->subject->getProcessedFiles());

        $fooFile = $this->buildProcessedFile('/bar/foo', '/foo/bar');
        $barFile = $this->buildProcessedFile('/foo/foo', '/bar/bar');

        $step = new Tests\Fixtures\DummyStep();
        $step->addProcessedFile($fooFile);
        $step->addProcessedFile($barFile);
        $this->subject->applyStep($step);

        self::assertSame([$fooFile, $barFile], $this->subject->getProcessedFiles());
        self::assertSame([$fooFile], $this->subject->getProcessedFiles('/foo'));
        self::assertSame([$barFile], $this->subject->getProcessedFiles('/bar'));
    }

    /**
     * @test
     */
    public function getWrittenDirectoryReturnsTemporaryDirectoryIfBuildWasNotMirrored(): void
    {
        self::assertSame($this->instructions->getTemporaryDirectory(), $this->subject->getWrittenDirectory());
    }

    /**
     * @test
     */
    public function getWrittenDirectoryReturnsTargetDirectoryIfBuildWasMirrored(): void
    {
        $this->subject->setMirrored(true);

        self::assertSame($this->instructions->getTargetDirectory(), $this->subject->getWrittenDirectory());
    }

    private function buildProcessedFile(string $sourceFile, string $targetFile): Src\Resource\Local\ProcessedFile
    {
        return new Src\Resource\Local\ProcessedFile(
            new Finder\SplFileInfo($sourceFile, dirname($sourceFile), basename($sourceFile)),
            new Finder\SplFileInfo($targetFile, dirname($targetFile), basename($targetFile)),
        );
    }
}
