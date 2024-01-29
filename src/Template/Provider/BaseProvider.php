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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace CPSIT\ProjectBuilder\Template\Provider;

use Composer\Factory;
use Composer\IO as ComposerIO;
use Composer\Package;
use Composer\Repository;
use Composer\Semver;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Paths;
use CPSIT\ProjectBuilder\Resource;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;
use Twig\Environment;
use Twig\Loader;
use UnexpectedValueException;

use function getenv;
use function sprintf;

/**
 * BaseProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class BaseProvider implements ProviderInterface
{
    protected Resource\Local\Composer $composer;
    protected Environment $renderer;
    protected ComposerIO\IOInterface $io;
    protected Package\Version\VersionParser $versionParser;
    protected bool $acceptInsecureConnections = false;

    public function __construct(
        protected IO\Messenger $messenger,
        protected Filesystem\Filesystem $filesystem,
    ) {
        $this->composer = new Resource\Local\Composer($this->filesystem);
        $this->renderer = new Environment(
            new Loader\FilesystemLoader([
                Filesystem\Path::join(
                    Helper\FilesystemHelper::getProjectRootPath(),
                    Paths::PROJECT_INSTALLER,
                ),
            ]),
        );
        $this->io = new ComposerIO\BufferIO();
        $this->versionParser = new Package\Version\VersionParser();
    }

    public function listTemplateSources(): array
    {
        $templateSources = [];

        $repository = $this->createRepository();

        $constraint = new Semver\Constraint\MatchAllConstraint();
        $searchResult = $repository->search(
            '',
            Repository\RepositoryInterface::SEARCH_FULLTEXT,
            self::PACKAGE_TYPE,
        );

        foreach ($searchResult as $result) {
            if (array_key_exists('abandoned', $result)) {
                continue;
            }

            $package = $repository->findPackage($result['name'], $constraint);

            if (null !== $package && self::PACKAGE_TYPE === $package->getType()) {
                $templateSources[] = $this->createTemplateSource($package);
            }
        }

        return $templateSources;
    }

    /**
     * @throws Exception\IOException
     * @throws Exception\InvalidTemplateSourceException
     * @throws Exception\MisconfiguredValidatorException
     */
    public function installTemplateSource(Template\TemplateSource $templateSource): void
    {
        $package = $templateSource->getPackage();

        // @codeCoverageIgnoreStart
        if ($package instanceof Package\AliasPackage) {
            $package = $package->getAliasOf();
            $templateSource->setPackage($package);
        }
        // @codeCoverageIgnoreEnd

        if ($package instanceof Package\Package) {
            $this->requestPackageVersionConstraint($templateSource);
        }

        $composerJson = $this->createComposerJson([$templateSource]);
        $output = new Console\Output\BufferedOutput();

        $this->messenger->progress(
            sprintf(
                'Installing project template%s...',
                $templateSource->shouldUseDynamicVersionConstraint()
                    ? ''
                    : sprintf(' (<info>%s</info>)', $templateSource->getPackage()->getPrettyVersion()),
            ),
            ComposerIO\IOInterface::NORMAL,
        );

        $exitCode = $this->composer->install($composerJson, false, $output);

        if (0 !== $exitCode) {
            $this->messenger->failed();
            $this->messenger->write($output->fetch());

            throw Exception\InvalidTemplateSourceException::forFailedInstallation($templateSource);
        }

        // Make sure installed sources are handled by Composer's class loader
        $loader = Resource\Local\Composer::createClassLoader(dirname($composerJson));
        $loader->register(true);

        // Look up installed package
        $composer = Resource\Local\Composer::createComposer(dirname($composerJson));
        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $installedPackage = $repository->findPackage($package->getName(), new Semver\Constraint\MatchAllConstraint());

        // Overwrite package from template source with actually installed template
        if (null !== $installedPackage) {
            $templateSource->setPackage($installedPackage);
        }

        // Show installed template version
        $this->messenger->progress(
            sprintf(
                'Installing project template (<info>%s</info>)...',
                $templateSource->getPackage()->getPrettyVersion(),
            ),
            ComposerIO\IOInterface::NORMAL,
            true,
        );
        $this->messenger->done();
        $this->messenger->newLine();
    }

    /**
     * @throws Exception\IOException
     * @throws Exception\InvalidTemplateSourceException
     * @throws Exception\MisconfiguredValidatorException
     */
    protected function requestPackageVersionConstraint(Template\TemplateSource $templateSource): void
    {
        $inputReader = $this->messenger->createInputReader();
        $repository = $templateSource->getPackage()->getRepository() ?? $this->createRepository();

        $this->messenger->writeWithEmoji(
            IO\Emoji::WhiteHeavyCheckMark->value,
            sprintf('Well done! You\'ve selected <comment>%s</comment>.', $templateSource->getPackage()->getName()),
        );

        $this->messenger->newLine();
        $this->messenger->write(
            sprintf('Do you require a specific version of <comment>%s</comment>?', $templateSource->getPackage()->getName()),
        );
        $this->messenger->comment(
            'If so, you may specify it here. Leave it empty and we\'ll find a current version for you.',
        );
        $this->messenger->newLine();
        $this->messenger->comment('Example: <fg=cyan>2.1.0</> or <fg=cyan>dev-feature/xyz</>');
        $this->messenger->newLine();

        $constraint = $inputReader->staticValue(
            'Enter the version constraint to require: ',
            validator: new IO\Validator\CallbackValidator([
                'callback' => $this->validateConstraint(...),
            ]),
        );

        $this->messenger->newLine();

        if (null === $constraint) {
            $templateSource->useDynamicVersionConstraint();

            return;
        }

        $package = $repository->findPackage($templateSource->getPackage()->getName(), $constraint);

        if ($package instanceof Package\BasePackage) {
            $templateSource->setPackage($package);

            return;
        }

        $this->messenger->error('Unable to find a package version for the given constraint.');

        if (!$inputReader->ask('Do you want to try another version constraint instead?')) {
            throw Exception\InvalidTemplateSourceException::forInvalidPackageVersionConstraint($templateSource, $constraint);
        }

        $this->messenger->newLine();

        $this->requestPackageVersionConstraint($templateSource);
    }

    protected function createTemplateSource(Package\BasePackage $package): Template\TemplateSource
    {
        return new Template\TemplateSource($this, $package);
    }

    /**
     * @param list<Template\TemplateSource>          $templateSources
     * @param list<array{type: string, url: string}> $repositories
     */
    protected function createComposerJson(array $templateSources, array $repositories = []): string
    {
        $repositories = [
            [
                'type' => $this->getRepositoryType(),
                'url' => $this->getUrl(),
            ],
            ...$repositories,
        ];

        $targetDirectory = Helper\FilesystemHelper::getNewTemporaryDirectory();
        $targetFile = Filesystem\Path::join($targetDirectory, 'composer.json');
        $composerJson = $this->renderer->render('composer.json.twig', [
            'templateSources' => $templateSources,
            'rootDir' => Helper\FilesystemHelper::getProjectRootPath(),
            'tempDir' => $targetDirectory,
            'repositories' => $repositories,
            'acceptInsecureConnections' => $this->acceptInsecureConnections,
            'simulatedRootPackageVersion' => getenv('PROJECT_BUILDER_SIMULATE_VERSION'),
        ]);

        $this->filesystem->dumpFile($targetFile, $composerJson);

        return $targetFile;
    }

    protected function createRepository(): Repository\RepositoryInterface
    {
        $config = Factory::createConfig($this->io);
        $config->merge([
            'config' => [
                'secure-http' => !$this->acceptInsecureConnections,
            ],
        ]);

        return Repository\RepositoryFactory::createRepo(
            $this->io,
            $config,
            [
                'type' => $this->getRepositoryType(),
                'url' => $this->getUrl(),
            ],
            Repository\RepositoryFactory::manager($this->io, $config, Factory::createHttpDownloader($this->io, $config)),
        );
    }

    /**
     * @throws Exception\ValidationException
     *
     * @internal
     */
    public function validateConstraint(?string $input): ?string
    {
        if (null === $input) {
            return null;
        }

        try {
            $this->versionParser->parseConstraints($input);
        } catch (UnexpectedValueException $exception) {
            throw Exception\ValidationException::create($exception->getMessage());
        }

        return $input;
    }

    /**
     * Get supported Composer repository type for the configured URL.
     *
     * @see https://getcomposer.org/doc/05-repositories.md#types
     */
    abstract protected function getRepositoryType(): string;
}
