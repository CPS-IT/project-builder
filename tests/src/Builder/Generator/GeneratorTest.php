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

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
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
        $this->subject = self::$container->get(Src\Builder\Generator\Generator::class);
        $this->eventListener = self::$container->get(Tests\Fixtures\DummyEventListener::class);
        $this->targetDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();
    }

    /**
     * @test
     */
    public function runRunsThroughAllConfiguredSteps(): void
    {
        self::$io->setUserInputs(['foo']);

        self::assertCount(0, $this->eventListener->dispatchedEvents);

        $actual = $this->subject->run($this->targetDirectory);

        self::assertTrue($actual->isStepApplied('collectBuildInstructions'));
        self::assertTrue($actual->isStepApplied('processSourceFiles'));
        self::assertTrue($actual->isStepApplied('processSharedSourceFiles'));
        self::assertTrue($actual->isStepApplied('mirrorProcessedFiles'));
        self::assertTrue($actual->isMirrored());

        $output = self::$io->getOutput();

        self::assertStringContainsString('Running step #1 "collectBuildInstructions"...', $output);
        self::assertStringContainsString('Running step #2 "processSourceFiles"...', $output);
        self::assertStringContainsString('Running step #3 "processSharedSourceFiles"...', $output);
        self::assertStringContainsString('Running step #4 "mirrorProcessedFiles"...', $output);

        self::assertFileExists($this->targetDirectory.'/dummy.yaml');
        self::assertStringEqualsFile($this->targetDirectory.'/dummy.yaml', 'name: "foo"'.PHP_EOL);

        self::assertCount(6, $this->eventListener->dispatchedEvents);
        self::assertInstanceOf(Src\Event\ProjectBuildStartedEvent::class, $this->eventListener->dispatchedEvents[0]);

        for ($i = 1; $i <= 4; ++$i) {
            self::assertInstanceOf(Src\Event\BuildStepProcessedEvent::class, $this->eventListener->dispatchedEvents[$i]);
        }

        self::assertInstanceOf(Src\Event\ProjectBuildFinishedEvent::class, $this->eventListener->dispatchedEvents[5]);
    }

    /**
     * @test
     */
    public function runRevertsAppliedStepsOnStepFailure(): void
    {
        $exception = null;

        self::$io->setUserInputs([]);

        try {
            $this->subject->run($this->targetDirectory);
        } catch (Src\Exception\StepFailureException $exception) {
        }

        self::assertInstanceOf(Src\Exception\StepFailureException::class, $exception);
        self::assertSame(1652954290, $exception->getCode());
        self::assertSame('Running step "collectBuildInstructions" failed. All applied steps were reverted.', $exception->getMessage());
        self::assertInstanceOf(Console\Exception\MissingInputException::class, $exception->getPrevious());

        self::assertCount(1, $this->subject->getRevertedSteps());
        self::assertInstanceOf(
            Src\Builder\Generator\Step\CollectBuildInstructionsStep::class,
            $this->subject->getRevertedSteps()[0]
        );

        self::assertCount(3, $this->eventListener->dispatchedEvents);
        self::assertInstanceOf(Src\Event\ProjectBuildStartedEvent::class, $this->eventListener->dispatchedEvents[0]);
        self::assertInstanceOf(Src\Event\BuildStepProcessedEvent::class, $this->eventListener->dispatchedEvents[1]);
        self::assertInstanceOf(Src\Event\BuildStepRevertedEvent::class, $this->eventListener->dispatchedEvents[2]);
    }

    /**
     * @test
     */
    public function runRevertsAppliedStepsAndExistsIfStoppableStepFailed(): void
    {
        self::$io->setUserInputs(['foo', 'no']);

        $actual = $this->subject->run($this->targetDirectory);

        self::assertCount(3, $actual->getAppliedSteps());
        self::assertFalse($actual->isMirrored());
    }

    /**
     * @test
     */
    public function cleanUpCleansUpRemainingFilesInTargetDirectory(): void
    {
        self::$io->setUserInputs(['foo']);

        $result = $this->subject->run($this->targetDirectory);

        $this->subject->cleanUp($result);

        self::assertTrue($result->isStepApplied('cleanUp'));
    }

    protected static function createConfig(): Src\Builder\Config\Config
    {
        $configReader = Src\Builder\Config\ConfigFactory::create();

        return $configReader->buildFromFile(
            dirname(__DIR__, 2).'/Fixtures/Templates/yaml-template/config.yaml'
        );
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
