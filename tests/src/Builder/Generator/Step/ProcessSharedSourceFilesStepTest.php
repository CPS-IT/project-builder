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
use PHPUnit\Framework;

/**
 * ProcessSharedSourceFilesStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessSharedSourceFilesStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\ProcessSharedSourceFilesStep $subject;
    private Src\Builder\BuildResult $result;

    protected function setUp(): void
    {
        parent::setUp();

        $step = $this->findStep();

        $this->subject = $this->container->get(Src\Builder\Generator\Step\ProcessSharedSourceFilesStep::class);
        $this->subject->setConfig($step);
        $this->result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($this->config, 'foo'),
        );
    }

    #[Framework\Attributes\Test]
    public function runProcessesSourceFilesAndAppliesStep(): void
    {
        $this->result->getInstructions()->addTemplateVariable('foo', 'baz');

        $actual = $this->subject->run($this->result);

        $temporaryDirectory = $this->result->getInstructions()->getTemporaryDirectory();

        self::assertTrue($actual);
        self::assertCount(4, $this->subject->getProcessedFiles());
        self::assertSame(
            'overrides/shared-dummy-4.yaml',
            $this->subject->getProcessedFiles()[0]->getTargetFile()->getRelativePathname(),
        );
        self::assertSame('shared-dummy.yaml', $this->subject->getProcessedFiles()[1]->getTargetFile()->getRelativePathname());
        self::assertFileExists(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'shared-dummy.yaml'),
        );
        self::assertFileDoesNotExist(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'shared-dummy-2.yaml'),
        );
        self::assertFileDoesNotExist(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'shared-dummy-3.yaml'),
        );
        self::assertFileDoesNotExist(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'shared-dummy-4.yaml'),
        );
        self::assertFileExists(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'overrides/shared-dummy-4.yaml'),
        );
        self::assertFileExists(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'foo-baz-shared-dummy/shared-dummy-1.yaml'),
        );
        self::assertFileExists(
            Src\Helper\FilesystemHelper::path($temporaryDirectory, 'foo-baz-shared-dummy/shared-dummy-2.yaml'),
        );
        self::assertTrue($this->result->isStepApplied($this->subject));
    }

    #[Framework\Attributes\Test]
    public function revertRemovesProcessedFiles(): void
    {
        $this->subject->run($this->result);

        self::assertFileExists(
            Src\Helper\FilesystemHelper::path($this->result->getInstructions()->getTemporaryDirectory(), 'shared-dummy.yaml'),
        );

        $this->subject->revert($this->result);

        self::assertFileDoesNotExist(
            Src\Helper\FilesystemHelper::path($this->result->getInstructions()->getTemporaryDirectory(), 'shared-dummy.yaml'),
        );
    }

    protected function createConfig(): Src\Builder\Config\Config
    {
        $configFactory = Src\Builder\Config\ConfigFactory::create();

        return $configFactory->buildFromFile(
            Src\Helper\FilesystemHelper::path(dirname(__DIR__, 3), 'Fixtures/Templates/yaml-template/config.yaml'),
            'yaml',
        );
    }

    private function findStep(): Src\Builder\Config\ValueObject\Step
    {
        foreach ($this->config->getSteps() as $step) {
            if (Src\Builder\Generator\Step\ProcessSharedSourceFilesStep::getType() === $step->getType()) {
                return $step;
            }
        }

        self::fail('Unable to find configured step.');
    }
}
