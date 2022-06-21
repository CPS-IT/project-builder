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
    private static Filesystem\Filesystem $filesystem;
    private static string $temporaryDirectory;

    private Src\Builder\Generator\Step\InstallComposerDependenciesStep $subject;
    private Src\Builder\BuildResult $buildResult;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\InstallComposerDependenciesStep::class);
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo')
        );
    }

    /**
     * @test
     */
    public function runInstallsComposerDependencies(): void
    {
        self::assertTrue($this->subject->run($this->buildResult));
        self::assertTrue($this->buildResult->isStepApplied($this->subject));
    }

    /**
     * @test
     */
    public function runWritesComposerInstallOutputAndFailsOnFailure(): void
    {
        $newConfig = self::createConfig();

        self::$config->setDeclaringFile($newConfig->getDeclaringFile());
        self::$filesystem->copy(
            dirname(__DIR__, 3).'/Fixtures/Files/invalid-composer.json',
            self::$temporaryDirectory.'/composer.json',
            true
        );

        self::assertFalse($this->subject->run($this->buildResult));
        self::assertStringContainsString(
            'Your requirements could not be resolved to an installable set of packages.',
            self::$io->getOutput()
        );
    }

    protected static function createConfig(): Src\Builder\Config\Config
    {
        $templateDirectory = dirname(__DIR__, 3).'/Fixtures/Templates/yaml-template';
        $finder = Finder\Finder::create()
            ->in($templateDirectory)
            ->notPath('vendor')
            ->notName('composer.lock')
        ;

        self::$temporaryDirectory = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();
        self::$filesystem = new Filesystem\Filesystem();
        self::$filesystem->mirror($templateDirectory, self::$temporaryDirectory, $finder);

        $configFactory = Src\Builder\Config\ConfigFactory::create();

        return $configFactory->buildFromFile(self::$temporaryDirectory.'/config.yaml');
    }

    protected function tearDown(): void
    {
        if (self::$filesystem->exists(self::$temporaryDirectory)) {
            self::$filesystem->remove(self::$temporaryDirectory);
        }
    }
}
