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
use Generator;

use function dirname;

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
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function runProcessesSourceFilesAndAppliesStep(): void
    {
        $actual = $this->subject->run($this->result);

        self::assertTrue($actual);
        self::assertCount(2, $this->subject->getProcessedFiles());
        self::assertSame('overrides/dummy-4.yaml', $this->subject->getProcessedFiles()[0]->getTargetFile()->getRelativePathname());
        self::assertSame('dummy.yaml', $this->subject->getProcessedFiles()[1]->getTargetFile()->getRelativePathname());
        self::assertFileExists($this->result->getInstructions()->getTemporaryDirectory().'/dummy.yaml');
        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy-2.yaml');
        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy-3.yaml');
        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy-4.yaml');
        self::assertFileExists($this->result->getInstructions()->getTemporaryDirectory().'/overrides/dummy-4.yaml');
        self::assertTrue($this->result->isStepApplied($this->subject));
    }

    /**
     * @param list<Src\Builder\Config\ValueObject\FileCondition> $fileConditions
     * @param list<string>                                       $notExpected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('runCanProcessTheSameSourceFileWithMultipleConditionsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function runCanProcessTheSameSourceFileWithMultipleConditions(
        array $fileConditions,
        string $expected,
        array $notExpected,
    ): void {
        $declaringFile = dirname(__DIR__, 3).'/Fixtures/Templates/yaml-template/config.yaml';
        $step = new Src\Builder\Config\ValueObject\Step(
            'processSourceFiles',
            new Src\Builder\Config\ValueObject\StepOptions($fileConditions),
        );
        $config = new Src\Builder\Config\Config('test', 'Test', [$step]);
        $config->setDeclaringFile($declaringFile);

        $result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($config, 'foo'),
        );

        $this->subject->setConfig($step);

        $this->subject->run($result);

        self::assertFileExists($result->getInstructions()->getTemporaryDirectory().'/'.$expected);

        foreach ($notExpected as $notExpectedFile) {
            self::assertFileDoesNotExist($result->getInstructions()->getTemporaryDirectory().'/'.$notExpectedFile);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revertRemovesProcessedFiles(): void
    {
        $this->subject->run($this->result);

        self::assertFileExists($this->result->getInstructions()->getTemporaryDirectory().'/dummy.yaml');

        $this->subject->revert($this->result);

        self::assertFileDoesNotExist($this->result->getInstructions()->getTemporaryDirectory().'/dummy.yaml');
    }

    /**
     * @return Generator<string, array{list<Src\Builder\Config\ValueObject\FileCondition>, string, list<string>}>
     */
    public static function runCanProcessTheSameSourceFileWithMultipleConditionsDataProvider(): Generator
    {
        yield 'one condition without target' => [
            [
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'true'),
            ],
            'dummy-2.yaml',
            ['dummy-2-moved.yaml'],
        ];
        yield 'one condition with target' => [
            [
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'true', 'dummy-2-moved.yaml'),
            ],
            'dummy-2-moved.yaml',
            ['dummy-2.yaml'],
        ];
        yield 'multiple condition without target' => [
            [
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'true'),
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'false'),
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'true'),
            ],
            'dummy-2.yaml',
            ['dummy-2-moved.yaml'],
        ];
        yield 'multiple condition with target' => [
            [
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'true', 'dummy-2-moved.yaml'),
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'false', 'dummy-2-not-moved.yaml'),
                new Src\Builder\Config\ValueObject\FileCondition('dummy-2.yaml', 'true', 'dummy-2-moved-again.yaml'),
            ],
            'dummy-2-moved.yaml',
            [
                'dummy-2.yaml',
                'dummy-2-not-moved.yaml',
                'dummy-2-moved-again.yaml',
            ],
        ];
    }

    protected static function createConfig(): Src\Builder\Config\Config
    {
        $configFactory = Src\Builder\Config\ConfigFactory::create();

        return $configFactory->buildFromFile(
            dirname(__DIR__, 3).'/Fixtures/Templates/yaml-template/config.yaml',
            'yaml',
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
