<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Martin Adler <mteu@mailbox.org>
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
use LogicException;
use PHPUnit\Framework;
use Symfony\Component\Filesystem;

/**
 * RunCommandStepTest.
 *
 * @author Martin Adler <mteu@mailbox.org>
 * @license GPL-3.0-or-later
 */
final class RunCommandStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\RunCommandStep $subject;
    private Src\Builder\BuildResult $result;
    private Filesystem\Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Generator\Step\RunCommandStep::class);
        $this->result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(
                $this->config,
                'foo',
            ),
        );
        $this->filesystem = new Filesystem\Filesystem();

        if (!$this->filesystem->exists($this->result->getWrittenDirectory())) {
            $this->filesystem->mkdir($this->result->getWrittenDirectory());
        }
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfNoCommandIsGiven(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1652952150);
        $this->expectExceptionMessage('The config for "options.command" does not exist or is not valid.');
        $this->subject->run($this->result);
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfRevertingIsAttempted(): never
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1687518806);
        $this->expectExceptionMessage('An already run command cannot be reverted.');
        $this->subject->revert($this->result);
    }

    #[Framework\Attributes\Test]
    public function runCommandIsSupported(): void
    {
        self::assertTrue(
            $this->subject::supports('runCommand'),
        );
    }

    #[Framework\Attributes\Test]
    public function runExecutesCommandWithoutConfirmationIfSkipConfirmationIsConfigured(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\RunCommandStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    command: 'echo \'foo\'',
                    skipConfirmation: true,
                ),
            ),
        );

        self::assertTrue($this->subject->run($this->result));
        self::assertFalse($this->subject->isStopped());
        self::assertStringNotContainsString('Do you wish to run this command?', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('runDoesNotExecuteCommandAndRespectsExecutionRequirementDataProvider')]
    public function runDoesNotExecuteCommandAndRespectsExecutionRequirement(bool $required, bool $expected): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\RunCommandStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    command: 'echo \'foo\'',
                    required: $required,
                ),
            ),
        );

        $this->io->setUserInputs(['no']);
        self::assertSame($expected, $this->subject->run($this->result));
        self::assertTrue($this->subject->isStopped());
    }

    #[Framework\Attributes\Test]
    public function runExecutesCommandAndAllowsExecutionFailures(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\RunCommandStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    command: 'foo',
                    allowFailure: true,
                ),
            ),
        );

        self::assertTrue($this->subject->run($this->result));
        self::assertFalse($this->subject->isStopped());
        self::assertStringContainsString('not found', $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function negatedQuestionForExecutionResultsInStoppedRun(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\RunCommandStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    command: 'echo \'foo\'',
                ),
            ),
        );

        $this->io->setUserInputs(['no']);
        self::assertFalse($this->subject->run($this->result));
        self::assertTrue($this->subject->isStopped());
    }

    #[Framework\Attributes\Test]
    public function invalidCommandPrintProcessErrorMessage(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\RunCommandStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    command: 'f00bär --?',
                ),
            ),
        );

        $this->io->setUserInputs(['yes']);
        self::assertFalse($this->subject->run($this->result));
    }

    /**
     * @return Generator<string, array{bool, bool}>
     */
    public static function runDoesNotExecuteCommandAndRespectsExecutionRequirementDataProvider(): Generator
    {
        yield 'required' => [true, false];
        yield 'optional' => [false, true];
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->result->getWrittenDirectory())) {
            $this->filesystem->remove($this->result->getWrittenDirectory());
        }
    }
}
