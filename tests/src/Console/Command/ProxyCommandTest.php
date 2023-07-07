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

namespace CPSIT\ProjectBuilder\Tests\Console\Command;

use Composer\Console;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console as SymfonyConsole;

/**
 * ProxyCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProxyCommandTest extends Tests\ContainerAwareTestCase
{
    private Tests\Fixtures\DummyCommand $command;
    private Src\Console\Command\ProxyCommand $subject;
    private SymfonyConsole\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->command = new Tests\Fixtures\DummyCommand();
        $this->subject = new Src\Console\Command\ProxyCommand(
            fn (Src\IO\Messenger $messenger) => $this->command->setMessenger($messenger),
        );
        $this->subject->setApplication(new Console\Application());
        $this->commandTester = new SymfonyConsole\Tester\CommandTester($this->subject);
    }

    #[Framework\Attributes\Test]
    public function executeConfiguresActualCommand(): void
    {
        $this->commandTester->execute([]);

        $inputDefinition = $this->subject->getDefinition();

        self::assertTrue($inputDefinition->hasArgument('dummy'));
        self::assertTrue($inputDefinition->hasOption('dummy'));
    }

    #[Framework\Attributes\Test]
    public function getNameReturnsActualCommandName(): void
    {
        $this->commandTester->execute([]);

        self::assertSame('dummy', $this->subject->getName());
    }

    #[Framework\Attributes\Test]
    public function getDescriptionReturnsActualCommandDescription(): void
    {
        $this->commandTester->execute([]);

        self::assertSame('dummy description', $this->subject->getDescription());
    }

    #[Framework\Attributes\Test]
    public function getHelpReturnsActualCommandHelp(): void
    {
        $this->commandTester->execute([]);

        self::assertSame('dummy help', $this->subject->getHelp());
    }

    #[Framework\Attributes\Test]
    public function getProcessedHelpReturnsActualProcessedCommandHelp(): void
    {
        $this->commandTester->execute([]);

        self::assertSame('dummy help', $this->subject->getProcessedHelp());
    }

    #[Framework\Attributes\Test]
    public function getSynopsisReturnsActualCommandSynopsis(): void
    {
        $this->commandTester->execute([]);

        self::assertSame('dummy [--dummy] [--] [<dummy>]', $this->subject->getSynopsis());
    }

    #[Framework\Attributes\Test]
    public function isProxyCommandReturnsTrue(): void
    {
        self::assertTrue($this->subject->isProxyCommand());
    }

    #[Framework\Attributes\Test]
    public function executeCallsActualCommandMethods(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('initialize was called', $output);
        self::assertStringContainsString('interact was called', $output);
        self::assertStringContainsString('execute was called', $output);
    }

    #[Framework\Attributes\Test]
    public function executePassesInitializedMessengerToActualCommand(): void
    {
        $this->commandTester->execute([]);

        $messenger = $this->command->getMessenger();

        self::assertStringNotContainsString('hello world', $this->commandTester->getDisplay());

        $messenger->write('hello world');

        self::assertStringContainsString('hello world', $this->commandTester->getDisplay());
    }
}
