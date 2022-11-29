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

use Composer\Package;
use Composer\Repository;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Resource;
use CPSIT\ProjectBuilder\Template;
use CPSIT\ProjectBuilder\Tests;
use donatj\MockWebServer;
use Generator;
use Symfony\Component\Filesystem;

use function array_map;
use function dirname;
use function json_encode;
use function sprintf;

/**
 * BaseComposerProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BaseComposerProviderTest extends Tests\ContainerAwareTestCase
{
    private Tests\Fixtures\DummyComposerProvider $subject;
    private MockWebServer\MockWebServer $server;

    protected function setUp(): void
    {
        $this->subject = new Tests\Fixtures\DummyComposerProvider(
            self::$container->get('app.messenger'),
            self::$container->get(Filesystem\Filesystem::class)
        );
        $this->server = new MockWebServer\MockWebServer();
        $this->server->start();

        $this->subject->url = sprintf('http://%s:%s', $this->server->getHost(), $this->server->getPort());
    }

    /**
     * @test
     *
     * @dataProvider listTemplateSourcesListsAllAvailableTemplateSourcesDataProvider
     *
     * @param list<Package\PackageInterface> $packages
     * @param list<Package\PackageInterface> $expected
     */
    public function listTemplateSourcesListsAllAvailableTemplateSources(array $packages, array $expected): void
    {
        $this->subject->packages = $packages;

        $expectedTemplateSources = array_map(
            fn (Package\PackageInterface $package) => new Template\TemplateSource($this->subject, $package),
            $expected
        );

        self::assertEquals($expectedTemplateSources, $this->subject->listTemplateSources());
    }

    /**
     * @test
     */
    public function installTemplateSourceThrowsExceptionIfInstallationFails(): void
    {
        $templateSource = new Template\TemplateSource($this->subject, $this->createPackage('foo/baz'));

        $this->expectExceptionObject(Exception\InvalidTemplateSourceException::forFailedInstallation($templateSource));

        $this->subject->installTemplateSource($templateSource);
    }

    /**
     * @test
     */
    public function installTemplateSourceInstallsComposerPackage(): void
    {
        $dumper = new Package\Dumper\ArrayDumper();
        $package = $this->createPackageFromTemplateFixture();
        $templateSource = new Template\TemplateSource($this->subject, $package);

        $this->server->setResponseOfPath(
            '/packages.json',
            new MockWebServer\Response(
                json_encode(
                    [
                        'packages' => [
                            $package->getName() => [
                                $package->getVersion() => $dumper->dump($package),
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR),
                [
                    'Content-Type' => 'application/json',
                ]
            )
        );

        $this->subject->installTemplateSource($templateSource);

        $output = self::$io->getOutput();

        self::assertStringContainsString('Installing template source... Done', $output);
    }

    /**
     * @test
     */
    public function createRepositoryReturnsComposerRepositoryForConfiguredUrl(): void
    {
        $actual = $this->subject->testCreateRepository();

        self::assertInstanceOf(Repository\ComposerRepository::class, $actual);
        self::assertSame($this->subject->getUrl(), $actual->getRepoConfig()['url']);
    }

    /**
     * @return Generator<string, array{list<Package\PackageInterface>, list<Package\PackageInterface>}>
     */
    public function listTemplateSourcesListsAllAvailableTemplateSourcesDataProvider(): Generator
    {
        yield 'no packages' => [
            [],
            [],
        ];
        yield 'unsupported packages only' => [
            [
                $this->createPackage('foo/baz-1', 'library'),
                $this->createPackage('foo/baz-2', 'library'),
                $this->createPackage('foo/baz-3', 'library'),
            ],
            [],
        ];
        yield 'unsupported and supported packages' => [
            [
                $this->createPackage('foo/baz-1', 'library'),
                $package1 = $this->createPackage('foo/baz-2'),
                $package2 = $this->createPackage('foo/baz-3'),
            ],
            [
                $package1,
                $package2,
            ],
        ];
    }

    private function createPackage(string $name, string $type = 'project-builder-template'): Package\Package
    {
        $package = new Package\Package($name, '1.0.0', '1.0.0');
        $package->setType($type);

        return $package;
    }

    private function createPackageFromTemplateFixture(string $templateName = 'json-template'): Package\Package
    {
        $fixturePath = dirname(__DIR__, 2).'/Fixtures/Templates/'.$templateName;

        self::assertDirectoryExists($fixturePath);

        $composerJson = Resource\Local\Composer::createComposer($fixturePath);
        $package = $this->createPackage($composerJson->getPackage()->getName());

        $package->setDistType('path');
        $package->setDistUrl($fixturePath);
        $package->setTransportOptions(['symlink' => false]);

        return $package;
    }

    protected function tearDown(): void
    {
        $this->server->stop();
    }
}
