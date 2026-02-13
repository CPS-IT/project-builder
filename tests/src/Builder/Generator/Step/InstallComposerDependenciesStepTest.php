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
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function dirname;

/**
 * InstallComposerDependenciesStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InstallComposerDependenciesStepTest extends Tests\ContainerAwareTestCase
{
    private Filesystem\Filesystem $filesystem;
    private string $temporaryDirectory;

    private Src\Builder\Generator\Step\InstallComposerDependenciesStep $subject;
    private Src\Builder\BuildResult $buildResult;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Generator\Step\InstallComposerDependenciesStep::class);
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions($this->config, 'foo'),
        );
    }

    #[Framework\Attributes\Test]
    public function runInstallsComposerDependencies(): void
    {
        self::assertTrue($this->subject->run($this->buildResult));
        self::assertTrue($this->buildResult->isStepApplied($this->subject));
    }

    #[Framework\Attributes\Test]
    public function runWritesComposerInstallOutputAndFailsOnFailure(): void
    {
        $newConfig = $this->createConfig();

        $this->config->setDeclaringFile($newConfig->getDeclaringFile());
        $this->filesystem->copy(
            Src\Helper\FilesystemHelper::path(dirname(__DIR__, 3), 'Fixtures/Files/invalid-composer.json'),
            Src\Helper\FilesystemHelper::path($this->temporaryDirectory, 'composer.json'),
            true,
        );

        self::assertFalse($this->subject->run($this->buildResult));
        self::assertStringContainsString(
            'Your requirements could not be resolved to an installable set of packages.',
            $this->io->getOutput(),
        );
    }

    protected function createConfig(): Src\Builder\Config\Config
    {
        $templateDirectory = Src\Helper\FilesystemHelper::path(dirname(__DIR__, 3), 'Fixtures/Templates/yaml-template');
        $finder = Finder\Finder::create()
            ->in($templateDirectory)
            ->notPath('vendor')
            ->notName('composer.lock')
        ;

        $this->temporaryDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();
        $this->filesystem = new Filesystem\Filesystem();
        $this->filesystem->mirror($templateDirectory, $this->temporaryDirectory, $finder);

        $configFactory = Src\Builder\Config\ConfigFactory::create();

        return $configFactory->buildFromFile(
            Src\Helper\FilesystemHelper::path($this->temporaryDirectory, 'config.yaml'),
            'yaml',
        );
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->temporaryDirectory)) {
            $this->filesystem->remove($this->temporaryDirectory);
        }
    }
}
