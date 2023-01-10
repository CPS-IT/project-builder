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

namespace CPSIT\ProjectBuilder\Tests\IO;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Exception;
use Generator;
use Symfony\Component\Filesystem;

use function implode;

/**
 * MessengerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MessengerTest extends Tests\ContainerAwareTestCase
{
    private Src\IO\Messenger $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get('app.messenger');
    }

    /**
     * @test
     */
    public function selectProviderCanHandlePackagistProvider(): void
    {
        $packagistProvider = new Src\Template\Provider\PackagistProvider(
            $this->subject,
            self::$container->get(Filesystem\Filesystem::class),
        );

        self::$io->setUserInputs(['']);

        self::assertSame($packagistProvider, $this->subject->selectProvider([$packagistProvider]));
    }

    /**
     * @test
     */
    public function selectProviderCanHandleCustomComposerProvider(): void
    {
        $customProvider = new Src\Template\Provider\ComposerProvider(
            $this->subject,
            self::$container->get(Filesystem\Filesystem::class),
        );

        self::$io->setUserInputs(['', 'https://www.example.com']);

        self::assertSame($customProvider, $this->subject->selectProvider([$customProvider]));
        self::assertSame('https://www.example.com', $customProvider->getUrl());
    }

    /**
     * @test
     */
    public function selectTemplateSourceThrowsExceptionIfGivenProviderListsNoTemplateSources(): void
    {
        $provider = new Tests\Fixtures\DummyProvider();

        $this->expectExceptionObject(Src\Exception\InvalidTemplateSourceException::forProvider($provider));

        $this->subject->selectTemplateSource($provider);
    }

    /**
     * @test
     *
     * @dataProvider selectTemplateSourceReturnsSelectedTemplateSourceDataProvider
     */
    public function selectTemplateSourceReturnsSelectedTemplateSource(
        Package\PackageInterface $package,
        string $expected,
    ): void {
        $provider = new Tests\Fixtures\DummyProvider();
        $templateSource = new Src\Template\TemplateSource($provider, $package);
        $provider->templateSources = [$templateSource];

        self::$io->setUserInputs(['']);

        self::assertSame($templateSource, $this->subject->selectTemplateSource($provider));
        self::assertStringContainsString($expected, self::$io->getOutput());
    }

    /**
     * @test
     *
     * @dataProvider confirmTemplateSourceRetryAsksForConfirmationAndReturnsResultDataProvider
     */
    public function confirmTemplateSourceRetryAsksForConfirmationAndReturnsResult(string $input, bool $expected): void
    {
        $exception = new Exception('Something went wrong.');

        self::$io->setUserInputs([$input]);

        self::assertSame($expected, $this->subject->confirmTemplateSourceRetry($exception));
        self::assertStringContainsString(
            implode(PHP_EOL, [
                'Something went wrong.',
                'You can go one step back and select another template provider.',
                'For more information, take a look at the documentation.',
                '',
                'Continue? [Y/n]',
            ]),
            self::$io->getOutput(),
        );
    }

    /**
     * @return Generator<string, array{Package\PackageInterface, string}>
     */
    public function selectTemplateSourceReturnsSelectedTemplateSourceDataProvider(): Generator
    {
        $package = new Package\Package('foo/baz', '1.0.0', '1.0.0');

        $completePackage = new Package\CompletePackage('foo/baz', '1.0.0', '1.0.0');
        $completePackage->setDescription('foo baz');

        $completePackageWithoutDescription = clone $completePackage;
        $completePackageWithoutDescription->setDescription(null);

        yield 'package' => [$package, 'foo/baz'];
        yield 'complete package' => [$completePackage, 'foo baz (foo/baz)'];
        yield 'complete package without description' => [$completePackageWithoutDescription, 'foo/baz'];
    }

    /**
     * @return Generator<string, array{string, bool}>
     */
    public function confirmTemplateSourceRetryAsksForConfirmationAndReturnsResultDataProvider(): Generator
    {
        yield 'default' => ['', true];
        yield 'yes' => ['yes', true];
        yield 'no' => ['no', false];
    }
}
