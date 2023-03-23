<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2022 Martin Adler <m.adler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Tests\Template\Provider;

use Composer\Json;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use ReflectionObject;
use ReflectionProperty;
use SebastianFeldmann\Cli;
use Symfony\Component\Filesystem;

use function chdir;
use function getcwd;
use function putenv;

/**
 * VcsProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @author Martin Adler <m.adler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsProviderTest extends Tests\ContainerAwareTestCase
{
    private Filesystem\Filesystem $filesystem;
    private Src\Template\Provider\VcsProvider $subject;
    private string $temporaryRootPath;

    protected function setUp(): void
    {
        $this->filesystem = self::$container->get(Filesystem\Filesystem::class);
        $this->subject = new Src\Template\Provider\VcsProvider(
            self::$container->get('app.messenger'),
            $this->filesystem,
        );
        $this->temporaryRootPath = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        $this->overwriteIO();
        $this->acceptInsecureConnections();

        putenv('PROJECT_BUILDER_ROOT_PATH='.$this->temporaryRootPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function requestCustomOptionsAsksAndAppliesBaseUrl(): void
    {
        self::$io->setUserInputs(['https://example.com']);

        $this->subject->requestCustomOptions(self::$container->get('app.messenger'));

        self::assertSame('https://example.com', $this->subject->getUrl());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlThrowsExceptionIfNoUrlIsConfigured(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidResourceException::create('url'));

        $this->subject->getUrl();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setUrlAppliesGivenUrl(): void
    {
        $this->subject->setUrl('https://example.org');

        self::assertSame('https://example.org', $this->subject->getUrl());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function listTemplateSourcesListsTemplatesFromConfiguredRepository(): void
    {
        $repoA = $this->initializeGitRepository('test/repo-a', ['test/repo-b' => '*']);

        self::$io->setUserInputs([$repoA]);

        $this->subject->requestCustomOptions(self::$container->get('app.messenger'));

        $actual = $this->subject->listTemplateSources();

        self::assertCount(1, $actual);
        self::assertCount(1, $actual[0]->getPackage()->getRequires());
        self::assertArrayHasKey('test/repo-b', $actual[0]->getPackage()->getRequires());

        $this->filesystem->remove($repoA);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function installTemplateSourceAsksForAdditionalRepositories(): void
    {
        $repoA = $this->initializeGitRepository('test/repo-a', ['test/repo-b' => '*']);
        $repoB = $this->initializeGitRepository('test/repo-b');

        self::$io->setUserInputs([
            $repoA,
            '',
            'yes',
            'vcs',
            $repoB,
            'no',
            '',
        ]);

        $this->subject->requestCustomOptions(self::$container->get('app.messenger'));

        [$templateSource] = $this->subject->listTemplateSources();

        self::assertDirectoryDoesNotExist($this->temporaryRootPath.'/.build/templates/repo-a');
        self::assertDirectoryDoesNotExist($this->temporaryRootPath.'/.build/templates/repo-b');

        $this->subject->installTemplateSource($templateSource);

        self::assertDirectoryExists($this->temporaryRootPath.'/.build/templates/repo-a');
        self::assertDirectoryExists($this->temporaryRootPath.'/.build/templates/repo-b');

        $output = self::$io->getOutput();
        $repositories = $this->fetchConfiguredRepositoriesViaReflection();

        self::assertSame(
            [
                ['type' => 'vcs', 'url' => $repoB],
            ],
            $repositories,
        );
        self::assertStringContainsString('Unable to install test/repo-a.', $output);
        self::assertStringContainsString('Are additional transitive packages required?', $output);
        self::assertStringContainsString('Package added.', $output);

        $this->filesystem->remove($repoA);
        $this->filesystem->remove($repoB);
    }

    private function overwriteIO(): void
    {
        $this->setPropertyValueOnObject($this->subject, 'io', self::$io);
    }

    private function acceptInsecureConnections(): void
    {
        $this->setPropertyValueOnObject($this->subject, 'acceptInsecureConnections', true);
    }

    /**
     * @return list<array{type: string, url: string}>
     */
    private function fetchConfiguredRepositoriesViaReflection(): array
    {
        $reflectionProperty = $this->getReflectionProperty($this->subject, 'repositories');

        /** @var list<array{type: string, url: string}> $repositories */
        $repositories = $reflectionProperty->getValue($this->subject);

        return $repositories;
    }

    private function setPropertyValueOnObject(object $object, string $propertyName, mixed $value): void
    {
        $reflectionProperty = $this->getReflectionProperty($object, $propertyName);
        $reflectionProperty->setValue($object, $value);
    }

    private function getReflectionProperty(object $object, string $propertyName): ReflectionProperty
    {
        $reflectionObject = new ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     * @param array<string, string> $requirements
     */
    private function initializeGitRepository(string $composerName, array $requirements = []): string
    {
        $repoDir = Src\Helper\FilesystemHelper::getNewTemporaryDirectory();

        // Create directory
        $this->filesystem->mkdir($repoDir);

        // Initialize repository
        self::executeInDirectory($repoDir, function (string $repoDir) use ($composerName, $requirements) {
            $runner = self::$container->get(Cli\Command\Runner::class);

            // Initialize repository
            $initCommand = (new Cli\Command\Executable('git'))
                ->addArgument('init')
            ;
            self::assertTrue($runner->run($initCommand)->isSuccessful());

            // Configure author
            $configEmailCommand = (new Cli\Command\Executable('git'))
                ->addArgument('config')
                ->addArgument('user.email')
                ->addArgument('noreply@example.com')
            ;
            self::assertTrue($runner->run($configEmailCommand)->isSuccessful());
            $configNameCommand = (new Cli\Command\Executable('git'))
                ->addArgument('config')
                ->addArgument('user.name')
                ->addArgument('Test bot')
            ;
            self::assertTrue($runner->run($configNameCommand)->isSuccessful());
            $configSignCommand = (new Cli\Command\Executable('git'))
                ->addArgument('config')
                ->addArgument('commit.gpgsign')
                ->addArgument('false')
            ;
            self::assertTrue($runner->run($configSignCommand)->isSuccessful());

            // Add composer.json
            $this->filesystem->dumpFile($repoDir.'/composer.json', Json\JsonFile::encode([
                'name' => $composerName,
                'type' => 'project-builder-template',
                'require' => $requirements,
            ]));

            // Create branch
            $checkoutCommand = (new Cli\Command\Executable('git'))
                ->addArgument('checkout')
                ->addOption('--orphan', 'main')
            ;
            self::assertTrue($runner->run($checkoutCommand)->isSuccessful());

            // Add files
            $addCommand = (new Cli\Command\Executable('git'))
                ->addArgument('add')
                ->addArgument('composer.json')
            ;
            self::assertTrue($runner->run($addCommand)->isSuccessful());

            // Commit files
            $commitCommand = (new Cli\Command\Executable('git'))
                ->addArgument('commit')
                ->addOption('--message', 'Add composer.json')
            ;
            self::assertTrue($runner->run($commitCommand)->isSuccessful());

            // Create tag
            $tagCommand = (new Cli\Command\Executable('git'))
                ->addArgument('tag')
                ->addArgument('1.0.0')
            ;
            self::assertTrue($runner->run($tagCommand)->isSuccessful());
        });

        return $repoDir;
    }

    /**
     * @param callable(string): void $code
     */
    private static function executeInDirectory(string $directory, callable $code): void
    {
        $currentWorkingDirectory = getcwd();

        self::assertIsString($currentWorkingDirectory, 'Unable to determine current working directory');
        self::assertTrue(chdir($directory), 'Unable to switch to temporary directory');

        $code($directory);

        chdir($currentWorkingDirectory);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->temporaryRootPath);

        putenv('PROJECT_BUILDER_ROOT_PATH');
    }
}
