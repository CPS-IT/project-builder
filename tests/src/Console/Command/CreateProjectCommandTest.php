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

namespace CPSIT\ProjectBuilder\Tests\Console\Command;

use Composer\Console;
use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Exception;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\Filesystem;

use function dirname;
use function sprintf;
use function substr_count;

/**
 * CreateProjectCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CreateProjectCommandTest extends Tests\ContainerAwareTestCase
{
    private string $targetDirectory;
    private Src\IO\Messenger $messenger;
    private Filesystem\Filesystem $filesystem;
    private Src\Tests\Fixtures\DummyProvider $templateProvider;
    private SymfonyConsole\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        $configReader = Src\Builder\Config\ConfigReader::create(dirname(__DIR__, 2).'/Fixtures/Templates');

        $this->messenger = self::$container->get('app.messenger');
        $this->targetDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();
        $this->filesystem = self::$container->get(Filesystem\Filesystem::class);
        $this->templateProvider = new Src\Tests\Fixtures\DummyProvider();

        $command = new Src\Console\Command\CreateProjectCommand(
            $this->messenger,
            $configReader,
            new Src\Error\ErrorHandler($this->messenger),
            $this->filesystem,
            [$this->templateProvider],
        );
        $command->setApplication(new Console\Application());

        $this->commandTester = new SymfonyConsole\Tester\CommandTester($command);

        self::$io->makeInteractive();
    }

    /**
     * @test
     */
    public function createReturnsInstanceWithGivenMessenger(): void
    {
        self::$io->makeInteractive(false);

        $input = new SymfonyConsole\Input\ArrayInput([
            'target-directory' => 'foo',
        ]);
        $output = new SymfonyConsole\Output\NullOutput();

        $actual = Src\Console\Command\CreateProjectCommand::create($this->messenger);
        $actual->run($input, $output);

        self::assertStringContainsString(
            'This command cannot be run in non-interactive mode.',
            self::$io->getOutput(),
        );
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfInputIsNonInteractive(): void
    {
        self::$io->makeInteractive(false);

        self::assertSame(1, $this->commandTester->execute([
            'target-directory' => $this->targetDirectory,
        ]));

        $output = self::$io->getOutput();

        self::assertStringContainsString('This command cannot be run in non-interactive mode.', $output);
    }

    /**
     * @test
     */
    public function executeShowsWelcomeScreen(): void
    {
        self::$io->setUserInputs(['no']);

        try {
            $this->commandTester->execute([
                'target-directory' => $this->targetDirectory,
            ]);
        } catch (Exception) {
            // Intended fallthrough.
        }

        $output = self::$io->getOutput();

        self::assertStringContainsString('Welcome to the CPS Project Builder', $output);
    }

    /**
     * @test
     */
    public function executeAllowsSelectingADifferentTemplateProviderIfTheSelectedProviderProvidesNoTemplates(): void
    {
        self::$io->setUserInputs(['yes', '', 'no']);

        try {
            $this->commandTester->execute([
                'target-directory' => $this->targetDirectory,
            ]);
        } catch (Exception) {
            // Intended fallthrough.
        }

        $output = self::$io->getOutput();

        self::assertStringContainsStringMultipleTimes('Fetching templates from https://www.example.com ...', $output);
        self::assertStringContainsString('Where can we find the project template?', $output);
    }

    /**
     * @test
     */
    public function executeAllowsSelectingADifferentTemplateProviderIfTheSelectedProviderShouldBeChanged(): void
    {
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        self::$io->setUserInputs(['1', '']);

        try {
            $this->commandTester->execute([
                'target-directory' => $this->targetDirectory,
            ]);
        } catch (Exception) {
            // Intended fallthrough.
        }

        $output = self::$io->getOutput();

        self::assertStringContainsStringMultipleTimes('Fetching templates from https://www.example.com ...', $output);
        self::assertStringContainsStringMultipleTimes('Try another template provider.', $output);
    }

    /**
     * @test
     */
    public function executeFailsIfProjectGenerationIsAborted(): void
    {
        $this->filesystem->dumpFile($this->targetDirectory.'/foo', 'baz');

        self::$io->setUserInputs(['no']);

        self::assertSame(2, $this->commandTester->execute([
            'target-directory' => $this->targetDirectory,
        ]));
    }

    /**
     * @test
     */
    public function executeHandlesErrorDuringProjectGeneration(): void
    {
        $this->templateProvider->installationPath = $this->targetDirectory;
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        self::$io->setUserInputs(['', '']);

        self::assertSame(1, $this->commandTester->execute([
            'target-directory' => $this->targetDirectory,
        ]));
        self::assertStringContainsString(
            'Running step "collectBuildInstructions" failed. All applied steps were reverted. [1652954290]',
            self::$io->getOutput(),
        );
    }

    /**
     * @test
     */
    public function executeSkipsOverwriteConfirmationIfForceOptionIsGiven(): void
    {
        $this->filesystem->dumpFile($this->targetDirectory.'/foo', 'baz');

        self::$io->setUserInputs(['no']);

        try {
            $this->commandTester->execute([
                'target-directory' => $this->targetDirectory,
                '--force' => true,
            ]);
        } catch (Exception) {
            // Intended fallthrough.
        }

        $output = self::$io->getOutput();

        self::assertStringNotContainsString(
            sprintf('The target directory "%s" is not empty.', $this->targetDirectory),
            $output,
        );
    }

    /**
     * @test
     */
    public function executeGeneratesNewProjectFromSelectedTemplate(): void
    {
        $this->templateProvider->installationPath = $this->targetDirectory;
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        self::$io->setUserInputs(['', '', 'foo', 'yes']);

        self::assertSame(0, $this->commandTester->execute([
            'target-directory' => $this->targetDirectory,
        ]));
        self::assertStringContainsString(
            'Congratulations, your new project was successfully built!',
            self::$io->getOutput(),
        );
    }

    private function createTemplateSource(): Src\Template\TemplateSource
    {
        $sourcePath = dirname(__DIR__, 2).'/Fixtures/Templates/json-template';
        $package = Src\Resource\Local\Composer::createComposer($sourcePath)->getPackage();

        self::assertInstanceOf(Package\Package::class, $package);

        $package->setSourceUrl($sourcePath);

        return new Src\Template\TemplateSource($this->templateProvider, $package);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->targetDirectory);
    }

    private static function assertStringContainsStringMultipleTimes(string $needle, string $haystack): void
    {
        self::assertGreaterThan(
            1,
            substr_count($haystack, $needle),
            sprintf('Failed asserting that "%s" contains "%s" multiple times', $haystack, $needle),
        );
    }
}
