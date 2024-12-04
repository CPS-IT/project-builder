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

namespace CPSIT\ProjectBuilder\Tests\Template\Provider;

use Composer\Cache;
use Composer\Package;
use Composer\Repository;
use Composer\Semver\Constraint;
use Composer\Semver\VersionParser;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use donatj\MockWebServer;
use Generator;
use PHPUnit\Framework;
use ReflectionObject;
use Symfony\Component\Filesystem;

use function array_map;
use function array_reduce;
use function dirname;
use function is_array;
use function json_encode;
use function reset;
use function sprintf;

/**
 * BaseProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BaseProviderTest extends Tests\ContainerAwareTestCase
{
    private Tests\Fixtures\DummyComposerProvider $subject;
    private MockWebServer\MockWebServer $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Tests\Fixtures\DummyComposerProvider(
            $this->container->get('app.messenger'),
            $this->container->get(Filesystem\Filesystem::class),
        );
        $this->server = new MockWebServer\MockWebServer();
        $this->server->start();

        $this->subject->url = sprintf('http://%s:%s', $this->server->getHost(), $this->server->getPort());
    }

    /**
     * @param list<Package\PackageInterface> $packages
     * @param list<Package\PackageInterface> $expected
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('listTemplateSourcesListsAllAvailableTemplateSourcesDataProvider')]
    public function listTemplateSourcesListsAllAvailableTemplateSources(array $packages, array $expected): void
    {
        $this->subject->packages = $packages;

        $expectedTemplateSources = array_map(
            fn (Package\PackageInterface $package) => new Src\Template\TemplateSource($this->subject, $package),
            $expected,
        );

        self::assertEquals($expectedTemplateSources, $this->subject->listTemplateSources());
    }

    #[Framework\Attributes\Test]
    public function listTemplateSourcesSkipsPackagesByConfiguration(): void
    {
        $package1 = self::createPackage('foo/baz-1');
        $package1->setExtra([
            'cpsit/project-builder' => [
                'exclude-from-listing' => true,
            ],
        ]);
        $package2 = self::createPackage('foo/baz-2');
        $package3 = self::createPackage('foo/baz-3');

        $this->subject->packages = [
            $package1,
            $package2,
            $package3,
        ];

        $expected = [
            new Src\Template\TemplateSource($this->subject, $package2),
            new Src\Template\TemplateSource($this->subject, $package3),
        ];

        self::assertEquals($expected, $this->subject->listTemplateSources());
    }

    #[Framework\Attributes\Test]
    public function installTemplateSourceThrowsExceptionIfInstallationFails(): void
    {
        $package = self::createPackage('foo/baz');
        $package->setRequires([
            'foo/boo' => new Package\Link(
                'foo/boo',
                'foo/boo',
                new Constraint\MatchAllConstraint(),
                Package\Link::TYPE_REQUIRE,
                '1.0.0',
            ),
        ]);
        $templateSource = new Src\Template\TemplateSource($this->subject, $package);

        $this->subject->packages = [$package];

        $this->mockPackagesServerResponse([$package]);

        $this->expectExceptionObject(Src\Exception\InvalidTemplateSourceException::forFailedInstallation($templateSource));

        $this->subject->installTemplateSource($templateSource);
    }

    #[Framework\Attributes\Test]
    public function installTemplateSourceThrowsExceptionIfInstallationFailsWithGivenConstraint(): void
    {
        $package = self::createPackage('foo/baz');
        $templateSource = new Src\Template\TemplateSource($this->subject, $package);

        $this->io->setUserInputs(['']);

        $this->expectExceptionObject(Src\Exception\InvalidTemplateSourceException::forFailedInstallation($templateSource));

        $this->subject->installTemplateSource($templateSource);
    }

    #[Framework\Attributes\Test]
    public function installTemplateSourceFailsSoftlyIfGivenConstraintIsInvalid(): void
    {
        $package = self::createPackageFromTemplateFixture();
        $templateSource = new Src\Template\TemplateSource($this->subject, $package);

        $this->subject->packages = [$package];

        $this->mockPackagesServerResponse([$package]);

        $this->io->setUserInputs(['foo', '']);

        $this->subject->installTemplateSource($templateSource);

        self::assertStringContainsString(
            'Could not parse version constraint foo: Invalid version string "foo"',
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function installTemplateSourceFailsIfGivenConstraintCannotBeResolved(): void
    {
        $package = self::createPackageFromTemplateFixture();
        $templateSource = new Src\Template\TemplateSource($this->subject, $package);

        $this->subject->packages = [$package];

        $this->mockPackagesServerResponse([$package]);

        $this->io->setUserInputs(['^2.0', 'no']);

        $this->expectExceptionObject(
            Src\Exception\InvalidTemplateSourceException::forInvalidPackageVersionConstraint($templateSource, '^2.0'),
        );

        $this->subject->installTemplateSource($templateSource);
    }

    #[Framework\Attributes\Test]
    public function installTemplateSourceAllowsSpecifyingOtherConstraintIfInstallationFailsWithGivenConstraint(): void
    {
        $package = self::createPackageFromTemplateFixture();
        $templateSource = new Src\Template\TemplateSource($this->subject, $package);

        $this->subject->packages = [$package];

        $this->mockPackagesServerResponse([$package]);

        $this->io->setUserInputs(['^2.0', 'yes', '']);

        self::assertFalse($templateSource->shouldUseDynamicVersionConstraint());

        $this->subject->installTemplateSource($templateSource);

        $output = $this->io->getOutput();

        self::assertStringContainsString('Installing project template (1.0.0)... Done', $output);
        self::assertTrue($templateSource->shouldUseDynamicVersionConstraint());
    }

    /**
     * @param non-empty-list<Package\PackageInterface> $packages
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('installTemplateSourceInstallsComposerPackageDataProvider')]
    public function installTemplateSourceInstallsComposerPackage(
        array $packages,
        string $constraint,
        string $expected,
    ): void {
        $templateSource = new Src\Template\TemplateSource($this->subject, reset($packages));

        $this->subject->packages = $packages;

        $this->mockPackagesServerResponse($packages);

        $this->io->setUserInputs([$constraint]);

        $this->subject->installTemplateSource($templateSource);

        self::assertStringContainsString($expected, $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function createRepositoryReturnsComposerRepositoryForConfiguredUrl(): void
    {
        $actual = $this->subject->testCreateRepository();

        self::assertInstanceOf(Repository\ComposerRepository::class, $actual);
        self::assertSame($this->subject->getUrl(), $actual->getRepoConfig()['url']);
    }

    #[Framework\Attributes\Test]
    public function createRepositoryReturnsComposerRepositoryWithDisabledCache(): void
    {
        $this->subject->disableCache();

        $actual = $this->subject->testCreateRepository();

        self::assertInstanceOf(Repository\ComposerRepository::class, $actual);

        $repositoryReflection = new ReflectionObject($actual);
        $cacheReflection = $repositoryReflection->getProperty('cache');
        $cache = $cacheReflection->getValue($actual);

        self::assertInstanceOf(Cache::class, $cache);
        self::assertFalse(Cache::isUsable($cache->getRoot()));
    }

    /**
     * @return Generator<string, array{list<Package\PackageInterface>, list<Package\PackageInterface>}>
     */
    public static function listTemplateSourcesListsAllAvailableTemplateSourcesDataProvider(): Generator
    {
        yield 'no packages' => [
            [],
            [],
        ];
        yield 'unsupported packages only' => [
            [
                self::createPackage('foo/baz-1', 'library'),
                self::createPackage('foo/baz-2', 'library'),
                self::createPackage('foo/baz-3', 'library'),
            ],
            [],
        ];

        $package1 = self::createPackage('foo/baz-2');
        $package2 = self::createPackage('foo/baz-3');

        yield 'unsupported and supported packages' => [
            [
                self::createPackage('foo/baz-1', 'library'),
                $package1,
                $package2,
            ],
            [
                $package1,
                $package2,
            ],
        ];

        $abandonedPackage1 = self::createPackage(name: 'foo/baz-1', abandoned: true);
        $abandonedPackage2 = self::createPackage(name: 'foo/baz-3', abandoned: 'foo/bar-3');
        $package1 = self::createPackage('foo/baz-2');
        $package2 = self::createPackage('foo/baz-4');
        $package3 = self::createPackage('foo/baz-5');

        yield 'abandoned packages after maintained' => [
            [
                $abandonedPackage1,
                $package1,
                $abandonedPackage2,
                $package2,
                $package3,
            ],
            [
                $package1,
                $package2,
                $package3,
                $abandonedPackage1,
                $abandonedPackage2,
            ],
        ];
    }

    /**
     * @return Generator<string, array{non-empty-list<Package\PackageInterface>, string, non-empty-string}>
     */
    public static function installTemplateSourceInstallsComposerPackageDataProvider(): Generator
    {
        yield 'no constraint' => [
            [self::createPackageFromTemplateFixture()],
            '',
            'Installing project template (1.0.0)... Done',
        ];

        yield 'constraint with one package' => [
            [self::createPackageFromTemplateFixture(prettyVersion: '1.1.0')],
            '^1.0',
            'Installing project template (1.1.0)... Done',
        ];

        yield 'constraint with multiple packages' => [
            [
                self::createPackageFromTemplateFixture(prettyVersion: '2.0.0'),
                self::createPackageFromTemplateFixture(prettyVersion: '1.2.0'),
                self::createPackageFromTemplateFixture(prettyVersion: '1.1.23'),
                self::createPackageFromTemplateFixture(prettyVersion: '1.1.0'),
                self::createPackageFromTemplateFixture(),
            ],
            '~1.1.0',
            'Installing project template (1.1.23)... Done',
        ];
    }

    private static function createPackage(
        string $name,
        string $type = 'project-builder-template',
        string $prettyVersion = '1.0.0',
        bool|string $abandoned = false,
    ): Package\CompletePackage {
        $versionParser = new VersionParser();

        $package = new Package\CompletePackage($name, $versionParser->normalize($prettyVersion), $prettyVersion);
        $package->setType($type);

        if (false !== $abandoned) {
            $package->setAbandoned($abandoned);
        }

        return $package;
    }

    private static function createPackageFromTemplateFixture(
        string $templateName = 'json-template',
        string $prettyVersion = '1.0.0',
    ): Package\Package {
        $fixturePath = dirname(__DIR__, 2).'/Fixtures/Templates/'.$templateName;

        self::assertDirectoryExists($fixturePath);

        $composerJson = Src\Resource\Local\Composer::createComposer($fixturePath);
        $package = self::createPackage($composerJson->getPackage()->getName(), prettyVersion: $prettyVersion);

        $package->setDistType('path');
        $package->setDistUrl($fixturePath);
        $package->setTransportOptions(['symlink' => false]);

        return $package;
    }

    /**
     * @param non-empty-list<Package\PackageInterface> $packages
     */
    private function mockPackagesServerResponse(array $packages): void
    {
        $dumper = new Package\Dumper\ArrayDumper();

        $this->server->setResponseOfPath(
            '/packages.json',
            new MockWebServer\Response(
                json_encode(
                    [
                        'packages' => array_reduce(
                            $packages,
                            static function (array $carry, Package\PackageInterface $package) use ($dumper): array {
                                $packageName = $package->getName();

                                if (!is_array($carry[$packageName] ?? null)) {
                                    $carry[$packageName] = [];
                                }

                                $carry[$packageName][$package->getPrettyVersion()] = $dumper->dump($package);

                                return $carry;
                            },
                            [],
                        ),
                    ],
                    JSON_THROW_ON_ERROR,
                ),
                [
                    'Content-Type' => 'application/json',
                ],
            ),
        );
    }

    protected function tearDown(): void
    {
        $this->server->stop();
    }
}
