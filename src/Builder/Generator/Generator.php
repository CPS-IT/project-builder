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

namespace CPSIT\ProjectBuilder\Builder\Generator;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Event;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;
use Symfony\Component\EventDispatcher;
use Symfony\Component\Filesystem;
use Throwable;

use function in_array;
use function sprintf;

/**
 * Generator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Generator
{
    private Builder\Config\Config $config;
    private IO\Messenger $messenger;
    private Step\StepFactory $stepFactory;
    private Filesystem\Filesystem $filesystem;
    private EventDispatcher\EventDispatcherInterface $eventDispatcher;

    /**
     * @var list<Step\StepInterface>
     */
    private array $revertedSteps = [];

    public function __construct(
        Builder\Config\Config $config,
        IO\Messenger $messenger,
        Builder\Generator\Step\StepFactory $stepFactory,
        Filesystem\Filesystem $filesystem,
        EventDispatcher\EventDispatcherInterface $eventDispatcher
    ) {
        $this->config = $config;
        $this->messenger = $messenger;
        $this->stepFactory = $stepFactory;
        $this->filesystem = $filesystem;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(string $targetDirectory): Builder\BuildResult
    {
        if (!$this->filesystem->exists($targetDirectory)) {
            $this->filesystem->mkdir($targetDirectory);
        }

        $instructions = new Builder\BuildInstructions($this->config, $targetDirectory);
        $result = new Builder\BuildResult($instructions);

        $this->eventDispatcher->dispatch(new Event\ProjectBuildStartedEvent($instructions));

        foreach ($this->config->getSteps() as $index => $step) {
            $currentStep = null;
            $exception = null;

            $this->messenger->comment(
                sprintf('Running step #%d "%s"...', $index + 1, $step->getType())
            );

            try {
                $currentStep = $this->stepFactory->get($step);
                $successful = $currentStep->run($result);
            } catch (\Exception $exception) {
                $successful = false;
            }

            if (null !== $currentStep) {
                $this->eventDispatcher->dispatch(
                    new Event\BuildStepProcessedEvent($currentStep, $result, $successful)
                );
            }

            $this->messenger->newLine();

            if (!$successful) {
                $this->handleStepFailure($result, $step->getType(), $currentStep, $exception);

                break;
            }
        }

        $this->eventDispatcher->dispatch(new Event\ProjectBuildFinishedEvent($result));

        return $result;
    }

    public function cleanUp(Builder\BuildResult $result): void
    {
        $step = new Builder\Generator\Step\CleanUpStep($this->filesystem);
        $step->run($result);
    }

    private function handleStepFailure(
        Builder\BuildResult $result,
        string $stepType,
        Step\StepInterface $step = null,
        Throwable $exception = null
    ): void {
        $this->messenger->error('Project generation failed. All processed steps will be reverted.');
        $this->messenger->newLine();

        if (null !== $step) {
            $this->revertStep($step, $result);
        }

        $this->revertAllSteps($result);

        $this->messenger->newLine();

        if ($step instanceof Step\StoppableStepInterface && $step->isStopped()) {
            return;
        }

        throw Exception\StepFailureException::create($stepType, $exception);
    }

    private function revertAllSteps(Builder\BuildResult $result): void
    {
        foreach (array_reverse($result->getAppliedSteps()) as $step) {
            $this->revertStep($step, $result);
        }
    }

    private function revertStep(Step\StepInterface $step, Builder\BuildResult $result): void
    {
        // Avoid reverting a step twice
        if (in_array($step, $this->revertedSteps, true)) {
            return;
        }

        $this->messenger->progress(sprintf('Reverting step "%s"...', $step::getType()));

        $step->revert($result);
        $this->revertedSteps[] = $step;

        $this->eventDispatcher->dispatch(new Event\BuildStepRevertedEvent($step, $result));

        $this->messenger->done();
    }

    /**
     * @return list<Step\StepInterface>
     */
    public function getRevertedSteps(): array
    {
        return $this->revertedSteps;
    }
}
