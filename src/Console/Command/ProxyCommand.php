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

namespace CPSIT\ProjectBuilder\Console\Command;

use Composer\Command;
use Composer\IO as ComposerIO;
use CPSIT\ProjectBuilder\Console;
use CPSIT\ProjectBuilder\IO;
use Symfony\Component\Console as SymfonyConsole;

/**
 * ProxyCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ProxyCommand extends Command\BaseCommand
{
    private ?Command\BaseCommand $command = null;
    private ?Command\BaseCommand $fullCommand = null;

    /**
     * @param callable(IO\Messenger): Command\BaseCommand $commandFactory
     */
    public function __construct(
        private $commandFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->getCommand()->setDefinition($this->getDefinition());
        $this->getCommand()->configure();
    }

    protected function initialize(
        SymfonyConsole\Input\InputInterface $input,
        SymfonyConsole\Output\OutputInterface $output,
    ): void {
        $this->getFullCommand($input, $output)->initialize($input, $output);
    }

    protected function interact(
        SymfonyConsole\Input\InputInterface $input,
        SymfonyConsole\Output\OutputInterface $output,
    ): void {
        $this->getFullCommand($input, $output)->interact($input, $output);
    }

    protected function execute(
        SymfonyConsole\Input\InputInterface $input,
        SymfonyConsole\Output\OutputInterface $output,
    ): int {
        return $this->getFullCommand($input, $output)->execute($input, $output);
    }

    public function getName(): ?string
    {
        return $this->getCommand()->getName();
    }

    public function getDescription(): string
    {
        return $this->getCommand()->getDescription();
    }

    public function getHelp(): string
    {
        return $this->getCommand()->getHelp();
    }

    public function getProcessedHelp(): string
    {
        return $this->getCommand()->getProcessedHelp();
    }

    public function getSynopsis(bool $short = false): string
    {
        return $this->getCommand()->getSynopsis($short);
    }

    public function isProxyCommand(): bool
    {
        return true;
    }

    private function getCommand(): Command\BaseCommand
    {
        if (null !== $this->command) {
            return $this->command;
        }

        return $this->command = $this->buildCommandWithIO(new ComposerIO\NullIO());
    }

    private function getFullCommand(
        SymfonyConsole\Input\InputInterface $input,
        SymfonyConsole\Output\OutputInterface $output,
    ): Command\BaseCommand {
        if (null !== $this->fullCommand) {
            return $this->fullCommand;
        }

        $io = new Console\IO\AccessibleConsoleIO($input, $output);

        $this->fullCommand = $this->buildCommandWithIO($io);
        $this->fullCommand->setApplication($this->getApplication());

        return $this->fullCommand;
    }

    private function buildCommandWithIO(ComposerIO\IOInterface $io): Command\BaseCommand
    {
        $messenger = IO\Messenger::create($io);

        return ($this->commandFactory)($messenger);
    }
}
