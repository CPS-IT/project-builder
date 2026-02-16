<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cspit/project-builder".
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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

use function dirname;

/**
 * GeneratorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GeneratorTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Generator $subject;
    private Tests\Fixtures\DummyEventListener $eventListener;
    private string $targetDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Generator\Generator::class);
        $this->eventListener = $this->container->get(Tests\Fixtures\DummyEventListener::class);
        $this->targetDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();
    }

    #[Framework\Attributes\Test]
    public function runRunsThroughAllConfiguredSteps(): void
    {
        $this->io->setUserInputs(['foo']);

        self::assertCount(0, $this->eventListener->dispatchedEvents);

        $actual = $this->subject->run($this->targetDirectory);

        self::assertTrue($actual->isStepApplied('collectBuildInstructions'));
        self::assertTrue($actual->isStepApplied('processSourceFiles'));
        self::assertTrue($actual->isStepApplied('processSharedSourceFiles'));
        self::assertTrue($actual->isStepApplied('generateBuildArtifact'));
        self::assertTrue($actual->isStepApplied('mirrorProcessedFiles'));
        self::assertTrue($actual->isStepApplied('runCommand'));
        self::assertTrue($actual->isMirrored());

        $output = $this->io->getOutput();

        self::assertStringContainsString('Running step #1 "collectBuildInstructions"...', $output);
        self::assertStringContainsString('Running step #2 "processSourceFiles"...', $output);
        self::assertStringContainsString('Running step #3 "processSharedSourceFiles"...', $output);
        self::assertStringContainsString('Running step #4 "generateBuildArtifact"...', $output);
        self::assertStringContainsString('Running step #5 "mirrorProcessedFiles"...', $output);
        self::assertStringContainsString('Running step #6 "runCommand"...', $output);

        self::assertFileExists(Src\Helper\FilesystemHelper::path($this->targetDirectory, 'dummy.yaml'));
        self::assertStringEqualsFile(
            Src\Helper\FilesystemHelper::path($this->targetDirectory, 'dummy.yaml'),
            'name: "foo"'.LF,
        );

        self::assertCount(11, $this->eventListener->dispatchedEvents);
        self::assertInstanceOf(Src\Event\ProjectBuildStartedEvent::class, $this->eventListener->dispatchedEvents[0]);
        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $this->eventListener->dispatchedEvents[1]);
        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $this->eventListener->dispatchedEvents[2]);

        for ($i = 3; $i <= 9; ++$i) {
            self::assertInstanceOf(Src\Event\BuildStepProcessedEvent::class, $this->eventListener->dispatchedEvents[$i]);
        }

        self::assertInstanceOf(Src\Event\ProjectBuildFinishedEvent::class, $this->eventListener->dispatchedEvents[10]);
    }

    #[Framework\Attributes\Test]
    public function runRestartsProjectGenerationOnStepFailure(): void
    {
        $this->io->setUserInputs(['', '', '', 'yes', 'foo']);

        self::assertCount(0, $this->eventListener->dispatchedEvents);

        $actual = $this->subject->run($this->targetDirectory);

        self::assertTrue($actual->isStepApplied('collectBuildInstructions'));
        self::assertTrue($actual->isStepApplied('processSourceFiles'));
        self::assertTrue($actual->isStepApplied('processSharedSourceFiles'));
        self::assertTrue($actual->isStepApplied('generateBuildArtifact'));
        self::assertTrue($actual->isStepApplied('mirrorProcessedFiles'));
        self::assertTrue($actual->isMirrored());

        $output = $this->io->getOutput();

        self::assertStringContainsString('If you want, you can restart project generation now.', $output);
        self::assertFileExists(Src\Helper\FilesystemHelper::path($this->targetDirectory, 'dummy.yaml'));
        self::assertStringEqualsFile(
            Src\Helper\FilesystemHelper::path($this->targetDirectory, 'dummy.yaml'),
            'name: "foo"'.LF,
        );

        self::assertCount(15, $this->eventListener->dispatchedEvents);
        self::assertInstanceOf(Src\Event\ProjectBuildStartedEvent::class, $this->eventListener->dispatchedEvents[0]);
        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $this->eventListener->dispatchedEvents[1]);
        self::assertInstanceOf(Src\Event\BuildStepProcessedEvent::class, $this->eventListener->dispatchedEvents[2]);
        self::assertInstanceOf(Src\Event\BuildStepRevertedEvent::class, $this->eventListener->dispatchedEvents[3]);
        self::assertInstanceOf(Src\Event\ProjectBuildStartedEvent::class, $this->eventListener->dispatchedEvents[4]);
        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $this->eventListener->dispatchedEvents[5]);
        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $this->eventListener->dispatchedEvents[6]);

        for ($i = 7; $i <= 13; ++$i) {
            self::assertInstanceOf(Src\Event\BuildStepProcessedEvent::class, $this->eventListener->dispatchedEvents[$i]);
        }

        self::assertInstanceOf(Src\Event\ProjectBuildFinishedEvent::class, $this->eventListener->dispatchedEvents[14]);
    }

    #[Framework\Attributes\Test]
    public function runRevertsAppliedStepsOnStepFailure(): void
    {
        $exception = null;

        $this->io->setUserInputs(['', '', '', 'no']);

        try {
            $this->subject->run($this->targetDirectory);
        } catch (Src\Exception\StepFailureException $exception) {
        }

        self::assertStringContainsString(
            'Project generation failed. All processed steps will be reverted',
            $this->io->getOutput(),
        );

        self::assertInstanceOf(Src\Exception\StepFailureException::class, $exception);
        self::assertSame(1652954290, $exception->getCode());
        self::assertSame('Running step "collectBuildInstructions" failed. All applied steps were reverted.', $exception->getMessage());
        self::assertInstanceOf(Src\Exception\ValidationException::class, $exception->getPrevious());

        self::assertCount(1, $this->subject->getRevertedSteps());
        self::assertInstanceOf(
            Src\Builder\Generator\Step\CollectBuildInstructionsStep::class,
            $this->subject->getRevertedSteps()[0],
        );

        self::assertCount(4, $this->eventListener->dispatchedEvents);
        self::assertInstanceOf(Src\Event\ProjectBuildStartedEvent::class, $this->eventListener->dispatchedEvents[0]);
        self::assertInstanceOf(Src\Event\BuildInstructionCollectedEvent::class, $this->eventListener->dispatchedEvents[1]);
        self::assertInstanceOf(Src\Event\BuildStepProcessedEvent::class, $this->eventListener->dispatchedEvents[2]);
        self::assertInstanceOf(Src\Event\BuildStepRevertedEvent::class, $this->eventListener->dispatchedEvents[3]);
    }

    #[Framework\Attributes\Test]
    public function runRevertsAppliedStepsAndExistsIfStoppableStepFailed(): void
    {
        $this->io->setUserInputs(['foo', 'no']);

        $actual = $this->subject->run($this->targetDirectory);

        self::assertCount(4, $actual->getAppliedSteps());
        self::assertFalse($actual->isMirrored());
    }

    #[Framework\Attributes\Test]
    public function runDisplaysExceptionMessageOnVerboseOutput(): void
    {
        $this->io->setVerbosity(Console\Output\OutputInterface::VERBOSITY_VERBOSE);
        $this->io->setUserInputs(['', '', '', 'no']);

        try {
            $this->subject->run($this->targetDirectory);
        } catch (Src\Exception\StepFailureException) {
        }

        $output = $this->io->getOutput();

        self::assertStringContainsString(
            'Exception: The given input must not be empty.',
            $output,
        );
        self::assertStringNotContainsString('#0', $output);
    }

    #[Framework\Attributes\Test]
    public function runDisplaysExceptionTraceOnVeryVerboseOutput(): void
    {
        $this->io->setVerbosity(Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->io->setUserInputs(['', '', '', 'no']);

        try {
            $this->subject->run($this->targetDirectory);
        } catch (Src\Exception\StepFailureException) {
        }

        self::assertStringContainsString(
            'Exception: The given input must not be empty.'.LF.'#0',
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function dumpArtifactDumpsBuildArtifact(): void
    {
        $this->io->setUserInputs(['foo']);

        $result = $this->subject->run($this->targetDirectory);

        $this->subject->dumpArtifact($result);

        self::assertTrue($result->isStepApplied('dumpBuildArtifact'));
    }

    #[Framework\Attributes\Test]
    public function cleanUpCleansUpRemainingFilesInTargetDirectory(): void
    {
        $this->io->setUserInputs(['foo']);

        $result = $this->subject->run($this->targetDirectory);

        $this->subject->cleanUp($result);

        self::assertTrue($result->isStepApplied('cleanUp'));
    }

    protected function createConfig(): Src\Builder\Config\Config
    {
        $configReader = Src\Builder\Config\ConfigFactory::create();

        $config = $configReader->buildFromFile(
            Src\Helper\FilesystemHelper::path(dirname(__DIR__, 2), 'Fixtures/Templates/yaml-template/config.yaml'),
            'yaml',
        );
        $config->setTemplateSource(
            new Src\Template\TemplateSource(
                new Tests\Fixtures\DummyProvider(),
                new Package\Package('foo/baz', '1.0.0', '1.0.0'),
            ),
        );

        return $config;
    }

    protected function tearDown(): void
    {
        $this->eventListener->dispatchedEvents = [];

        $filesystem = new Filesystem\Filesystem();

        if ($filesystem->exists($this->targetDirectory)) {
            $filesystem->remove($this->targetDirectory);
        }
    }
}
