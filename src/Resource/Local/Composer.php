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

namespace CPSIT\ProjectBuilder\Resource\Local;

use Composer\Autoload;
use Composer\Factory;
use Composer\InstalledVersions;
use Composer\IO;
use Composer\Package;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\Filesystem;
use Symfony\Component\Process;

use function array_filter;
use function basename;
use function dirname;
use function in_array;
use function is_array;
use function is_executable;
use function is_file;
use function is_string;

/**
 * Composer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class Composer
{
    public function __construct(
        private Filesystem\Filesystem $filesystem,
        private Process\ExecutableFinder $executableFinder = new Process\ExecutableFinder(),
    ) {}

    /**
     * @template T of SymfonyConsole\Output\OutputInterface|null
     *
     * @param T $output
     *
     * @param-out (T is null ? SymfonyConsole\Output\BufferedOutput : T) $output
     */
    public function install(
        string $composerJson,
        bool $includeDevDependencies = false,
        ?SymfonyConsole\Output\OutputInterface &$output = null,
    ): int {
        if (!$this->filesystem->exists($composerJson)) {
            throw Exception\IOException::forMissingFile($composerJson);
        }

        if (null === $output) {
            $output = new SymfonyConsole\Output\BufferedOutput();
        }

        $command = [
            ...$this->resolveComposerBinary(),
            'update',
            '--prefer-dist',
            '--no-interaction',
        ];

        if (!$includeDevDependencies) {
            $command[] = '--no-dev';
        }

        // Run Composer in a dedicated process. Running it in-process would
        // load the template's dependencies (autoload "files", plugins, ...)
        // into this very process, which can clash with dependencies already
        // loaded for the project builder itself (e.g. duplicate global
        // function declarations of a shared dependency).
        $process = new Process\Process(
            $command,
            dirname($composerJson),
            ['COMPOSER' => basename($composerJson)],
            null,
            null,
        );
        $process->disableOutput();
        $process->run(static function (string $type, string $line) use ($output): void {
            $output->write($line);
        });

        return (int) $process->getExitCode();
    }

    /**
     * @return non-empty-list<string>
     */
    private function resolveComposerBinary(): array
    {
        $binary = $this->executableFinder->find('composer');

        if (null !== $binary) {
            return [$binary];
        }

        // Fall back to the binary/script that runs the current process,
        // e.g. when Composer was invoked as a local "composer.phar" that
        // is not registered on the system's PATH.
        $argv = $_SERVER['argv'] ?? null;
        $fallback = is_array($argv) ? $argv[0] ?? null : null;

        if (is_string($fallback) && is_file($fallback)) {
            return is_executable($fallback) ? [$fallback] : [PHP_BINARY, $fallback];
        }

        return ['composer'];
    }

    /**
     * @internal
     */
    public static function createClassLoader(?string $rootPath = null): Autoload\ClassLoader
    {
        $rootPath ??= Helper\FilesystemHelper::getPackageDirectory();
        $composer = self::createComposer($rootPath);

        // Get all packages of type "project-builder-template"
        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $templatePackages = InstalledVersions::getInstalledPackagesByType(Template\Provider\ProviderInterface::PACKAGE_TYPE);
        $packages = array_filter(
            $repository->getPackages(),
            fn (Package\BasePackage $package): bool => in_array($package->getName(), $templatePackages, true),
        );
        $packages[] = $composer->getPackage();

        // Parse autoloads of template packages
        $autoloadGenerator = $composer->getAutoloadGenerator();
        $packageMap = $autoloadGenerator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $packages);
        $autoloads = $autoloadGenerator->parseAutoloads($packageMap, $composer->getPackage());

        // Fetch vendor directory
        $vendorDir = $composer->getConfig()->get('vendor-dir');

        return $autoloadGenerator->createLoader($autoloads, $vendorDir);
    }

    /**
     * @internal
     */
    public static function createComposer(string $rootPath): \Composer\Composer
    {
        $factory = new Factory();

        return $factory->createComposer(
            new IO\NullIO(),
            Filesystem\Path::join($rootPath, 'composer.json'),
        );
    }
}
