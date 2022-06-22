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
use Composer\Console;
use Composer\Factory;
use Composer\InstalledVersions;
use Composer\IO;
use Composer\Package;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\Filesystem;
use function array_filter;
use function assert;
use function basename;
use function dirname;
use function getenv;
use function in_array;
use function is_string;
use function putenv;

/**
 * Composer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Composer
{
    private Filesystem\Filesystem $filesystem;

    public function __construct(Filesystem\Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function install(
        string $composerJson,
        bool $includeDevDependencies = false,
        SymfonyConsole\Output\OutputInterface &$output = null
    ): int {
        if (!$this->filesystem->exists($composerJson)) {
            throw Exception\IOException::forMissingFile($composerJson);
        }

        $initialComposerEnvValue = getenv('COMPOSER');

        putenv('COMPOSER='.basename($composerJson));

        $input = new SymfonyConsole\Input\ArrayInput([
            'command' => 'update',
            '--working-dir' => dirname($composerJson),
            '--no-dev' => !$includeDevDependencies,
            '--prefer-dist' => true,
        ]);
        $input->setInteractive(false);

        if (null === $output) {
            $output = new SymfonyConsole\Output\BufferedOutput();
        }

        $application = new Console\Application();
        $application->setAutoExit(false);
        $exitCode = $application->run($input, $output);

        if (false !== $initialComposerEnvValue) {
            putenv('COMPOSER='.$initialComposerEnvValue);
        } else {
            putenv('COMPOSER');
        }

        return $exitCode;
    }

    /**
     * @internal
     */
    public static function createClassLoader(): Autoload\ClassLoader
    {
        $rootPath = Helper\FilesystemHelper::getProjectRootPath();
        $composer = self::createComposer($rootPath);

        // Get all packages of type "project-builder-template"
        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $templatePackages = InstalledVersions::getInstalledPackagesByType('project-builder-template');
        $packages = array_filter(
            $repository->getPackages(),
            fn (Package\BasePackage $package): bool => in_array($package->getName(), $templatePackages, true)
        );
        $packages[] = $composer->getPackage();

        // Parse autoloads of template packages
        $autoloadGenerator = $composer->getAutoloadGenerator();
        $packageMap = $autoloadGenerator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $packages);
        $autoloads = $autoloadGenerator->parseAutoloads($packageMap, $composer->getPackage());

        // Fetch vendor directory
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        if (!is_string($vendorDir)) {
            $vendorDir = null;
        }

        return $autoloadGenerator->createLoader($autoloads, $vendorDir);
    }

    /**
     * @internal
     */
    private static function createComposer(string $rootPath): \Composer\Composer
    {
        $factory = new Factory();
        $composer = $factory->createComposer(new IO\NullIO(), null, false, $rootPath);
        assert($composer instanceof \Composer\Composer);

        return $composer;
    }
}
