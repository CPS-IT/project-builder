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

namespace CPSIT\ProjectBuilder\Builder\Generator\Step;

use Composer\IO as ComposerIO;
use CPSIT\ProjectBuilder\Builder\BuildResult;
use CPSIT\ProjectBuilder\Exception\InvalidConfigurationException;
use CPSIT\ProjectBuilder\IO;
use LogicException;
use Symfony\Component\Process\Process;

/**
 * RunCommandStep.
 *
 * @author Martin Adler <mteu@mailbox.org>
 * @license GPL-3.0-or-later
 */
final class RunCommandStep extends AbstractStep implements StepInterface, StoppableStepInterface
{
    private const TYPE = 'runCommand';
    private bool $stopped = false;

    public function __construct(
        private readonly IO\Messenger $messenger,
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function run(BuildResult $buildResult): bool
    {
        $command = $this->config->getOptions()->getCommand() ?? throw InvalidConfigurationException::create('options.command');

        if (!$this->config->getOptions()->shouldSkipConfirmation() && !$this->messenger->confirmRunCommand($command)) {
            $this->stopped = true;

            return false;
        }

        $this->messenger->newLine(ComposerIO\IOInterface::VERBOSE);

        $process = Process::fromShellCommandline(
            $command,
            $buildResult->getWrittenDirectory(),
        );

        $addNewLine = !$this->config->getOptions()->shouldSkipConfirmation();

        $process->run(function (string $type, string $buffer) use (&$addNewLine): void {
            if ($addNewLine) {
                $this->messenger->newLine();

                $addNewLine = false;
            }

            $this->messenger->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            return false;
        }

        $buildResult->applyStep($this);

        return true;
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }

    public function revert(BuildResult $buildResult): never
    {
        throw new LogicException('An already run command cannot be reverted.', 1687518806);
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }
}
