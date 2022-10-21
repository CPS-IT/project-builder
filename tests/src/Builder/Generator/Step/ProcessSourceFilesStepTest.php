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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;

/**
 * ProcessSourceFilesStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessSourceFilesStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\ProcessSourceFilesStep $subject;
    private Src\Builder\BuildResult $result;

    protected function setUp(): void
    {
        $step = $this->findStep();

        $this->subject = self::$container->get(Src\Builder\Generator\Step\ProcessSourceFilesStep::class);
        $this->subject->setConfig($step);
        $this->result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo')
        );
    }

    /**
     * @test
     */
    public function runProcessesSourceFilesAndAppliesStep(): void
    {
        $actual = $this->subject->run($this->result);

        self::assertTrue($actual);
        self::assertCount(1, $this->subject->getProcessedFiles());
        self::assertSame('dummy.yaml', $this->subject->getProcessedFiles()[0]->getTargetFile()->getRelativePathname());
        self::assertFileExists($this->result->getInstructions()->getTemporaryDirectory().'/dummy.yaml');
        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy-2.yaml');
        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy-3.yaml');
        self::assertTrue($this->result->isStepApplied($this->subject));
    }

    /**
     * @test
     */
    public function revertRemovesProcessedFiles(): void
    {
        $this->subject->run($this->result);

        self::assertFileExists($this->result->getInstructions()->getTemporaryDirectory().'/dummy.yaml');

        $this->subject->revert($this->result);

        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy.yaml');
    }

    protected static function createConfig(): Src\Builder\Config\Config
    {
        $configFactory = Src\Builder\Config\ConfigFactory::create();

        return $configFactory->buildFromFile(
            dirname(__DIR__, 3).'/Fixtures/Templates/yaml-template/config.yaml',
            'yaml'
        );
    }

    private function findStep(): Src\Builder\Config\ValueObject\Step
    {
        foreach (self::$config->getSteps() as $step) {
            if (Src\Builder\Generator\Step\ProcessSourceFilesStep::getType() === $step->getType()) {
                return $step;
            }
        }

        self::fail('Unable to find configured step.');
    }
}
