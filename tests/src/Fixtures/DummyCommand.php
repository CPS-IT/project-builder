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

namespace CPSIT\ProjectBuilder\Tests\Fixtures;

use Composer\Command;
use Composer\IO as ComposerIO;
use CPSIT\ProjectBuilder\IO;
use Symfony\Component\Console;

/**
 * DummyCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyCommand extends Command\BaseCommand
{
    private IO\Messenger $messenger;

    public function __construct()
    {
        parent::__construct('dummy');
        $this->messenger = IO\Messenger::create(new ComposerIO\NullIO());
    }

    protected function configure(): void
    {
        $this->setDescription('dummy description');
        $this->setHelp('dummy help');

        $this->addArgument('dummy');
        $this->addOption('dummy');
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->messenger->write('initialize was called');
    }

    protected function interact(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->messenger->write('interact was called');
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $this->messenger->write('execute was called');

        return 0;
    }

    public function getMessenger(): IO\Messenger
    {
        return $this->messenger;
    }

    public function setMessenger(IO\Messenger $messenger): self
    {
        $this->messenger = $messenger;

        return $this;
    }
}
