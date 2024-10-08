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

namespace CPSIT\ProjectBuilder\Tests\Console;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Filesystem;

use function dirname;
use function sprintf;
use function substr_count;

/**
 * ApplicationTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ApplicationTest extends Tests\ContainerAwareTestCase
{
    private string $targetDirectory;
    private Src\IO\Messenger $messenger;
    private Filesystem\Filesystem $filesystem;
    private Tests\Fixtures\DummyProvider $templateProvider;
    private Src\Builder\Config\ConfigReader $configReader;
    private Src\Console\Application $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->targetDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();
        $this->messenger = $this->container->get('app.messenger');
        $this->filesystem = $this->container->get(Filesystem\Filesystem::class);
        $this->templateProvider = new Tests\Fixtures\DummyProvider();
        $this->configReader = Src\Builder\Config\ConfigReader::create(dirname(__DIR__).'/Fixtures/Templates');
        $this->subject = new Src\Console\Application(
            $this->messenger,
            $this->configReader,
            new Src\Error\ErrorHandler($this->messenger),
            $this->filesystem,
            $this->targetDirectory,
            [$this->templateProvider],
        );

        $this->filesystem->mirror(dirname(__DIR__, 2), $this->targetDirectory);

        $this->io->makeInteractive();
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfInputIsNonInteractive(): void
    {
        $this->io->makeInteractive(false);

        self::assertSame(1, $this->subject->run());

        $output = $this->io->getOutput();

        self::assertStringContainsString('This command cannot be run in non-interactive mode.', $output);
    }

    #[Framework\Attributes\Test]
    public function runMirrorsSourceFilesToTemporaryDirectory(): void
    {
        $temporaryDirectory = $this->targetDirectory.'/.build/src';

        self::assertDirectoryDoesNotExist($temporaryDirectory);

        $this->io->setUserInputs(['no']);

        $this->subject->run();

        self::assertDirectoryExists($temporaryDirectory);
    }

    #[Framework\Attributes\Test]
    public function runShowsWelcomeScreen(): void
    {
        $this->io->setUserInputs(['no']);

        $this->subject->run();

        $output = $this->io->getOutput();

        self::assertStringContainsString('Welcome to the Project Builder', $output);
    }

    #[Framework\Attributes\Test]
    public function runAllowsSelectingADifferentTemplateProviderIfTheSelectedProviderProvidesNoTemplates(): void
    {
        $this->io->setUserInputs(['yes', '', 'no']);

        $this->subject->run();

        $output = $this->io->getOutput();

        self::assertStringContainsStringMultipleTimes('Fetching templates from https://www.example.com ...', $output);
        self::assertStringContainsString('Where can we find the project template?', $output);
    }

    #[Framework\Attributes\Test]
    public function runAllowsSelectingADifferentTemplateProviderIfTheSelectedProviderShouldBeChanged(): void
    {
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        $this->io->setUserInputs(['1', '']);

        $this->subject->run();

        $output = $this->io->getOutput();

        self::assertStringContainsStringMultipleTimes('Fetching templates from https://www.example.com ...', $output);
        self::assertStringContainsStringMultipleTimes('Try a different provider (e.g. Satis or GitHub)', $output);
    }

    #[Framework\Attributes\Test]
    public function runFailsIfProjectGenerationIsAborted(): void
    {
        $this->templateProvider->installationPath = $this->targetDirectory;
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        $this->io->setUserInputs(['', '', 'foo', 'no']);

        self::assertSame(2, $this->subject->run());
    }

    #[Framework\Attributes\Test]
    public function runHandlesErrorDuringProjectGeneration(): void
    {
        $this->templateProvider->installationPath = $this->targetDirectory;
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        $this->io->setUserInputs(['', '', '', '', 'no']);

        self::assertSame(1, $this->subject->run());
        self::assertStringContainsString(
            'Running step "collectBuildInstructions" failed. All applied steps were reverted. [1652954290]',
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function runGeneratesNewProjectFromSelectedTemplate(): void
    {
        $this->templateProvider->installationPath = $this->targetDirectory;
        $this->templateProvider->templateSources = [
            $this->createTemplateSource(),
        ];

        $this->io->setUserInputs(['', '', 'foo', 'yes']);

        self::assertSame(0, $this->subject->run());
        self::assertStringContainsString(
            'Congratulations, your new project was successfully built!',
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function runUsesDefaultTemplateProvidersIfNoProvidersAreConfigured(): void
    {
        /** @var Src\Template\Provider\PackagistProvider $packagistProvider */
        $packagistProvider = $this->container->get(Src\Template\Provider\PackagistProvider::class);
        $packageCount = count($packagistProvider->listTemplateSources());

        $subject = new Src\Console\Application(
            $this->messenger,
            $this->configReader,
            new Src\Error\ErrorHandler($this->messenger),
            $this->filesystem,
            $this->targetDirectory,
        );

        $this->io->setUserInputs([(string) $packageCount]);

        $subject->run();

        $output = $this->io->getOutput();

        self::assertStringContainsString(Src\Template\Provider\PackagistProvider::getName(), $output);
        self::assertStringContainsString(Src\Template\Provider\ComposerProvider::getName(), $output);
        self::assertStringContainsString(Src\Template\Provider\VcsProvider::getName(), $output);
    }

    private function createTemplateSource(): Src\Template\TemplateSource
    {
        $sourcePath = dirname(__DIR__).'/Fixtures/Templates/json-template';
        $package = Src\Resource\Local\Composer::createComposer($sourcePath)->getPackage();

        self::assertInstanceOf(Package\Package::class, $package);

        $package->setSourceUrl($sourcePath);

        return new Src\Template\TemplateSource($this->templateProvider, $package);
    }

    protected function tearDown(): void
    {
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
