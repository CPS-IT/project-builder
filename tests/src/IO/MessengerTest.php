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
use PHPUnit\Framework;
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
        parent::setUp();

        $this->subject = $this->container->get('app.messenger');
    }

    #[Framework\Attributes\Test]
    public function selectProviderCanHandlePackagistProvider(): void
    {
        $packagistProvider = new Src\Template\Provider\PackagistProvider(
            $this->subject,
            $this->container->get(Filesystem\Filesystem::class),
        );

        $this->io->setUserInputs(['']);

        self::assertSame($packagistProvider, $this->subject->selectProvider([$packagistProvider]));
    }

    #[Framework\Attributes\Test]
    public function selectProviderCanHandleCustomComposerProvider(): void
    {
        $customProvider = new Src\Template\Provider\ComposerProvider(
            $this->subject,
            $this->container->get(Filesystem\Filesystem::class),
        );

        $this->io->setUserInputs(['', 'https://www.example.com']);

        self::assertSame($customProvider, $this->subject->selectProvider([$customProvider]));
        self::assertSame('https://www.example.com', $customProvider->getUrl());
    }

    #[Framework\Attributes\Test]
    public function selectTemplateSourceThrowsExceptionIfGivenProviderListsNoTemplateSources(): void
    {
        $provider = new Tests\Fixtures\DummyProvider();

        $this->expectExceptionObject(Src\Exception\InvalidTemplateSourceException::forProvider($provider));

        $this->subject->selectTemplateSource($provider);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('selectTemplateSourceReturnsSelectedTemplateSourceDataProvider')]
    public function selectTemplateSourceReturnsSelectedTemplateSource(
        Package\PackageInterface $package,
        string $expected,
    ): void {
        $provider = new Tests\Fixtures\DummyProvider();
        $templateSource = new Src\Template\TemplateSource($provider, $package);
        $provider->templateSources = [$templateSource];

        $this->io->setUserInputs(['']);

        self::assertSame($templateSource, $this->subject->selectTemplateSource($provider));
        self::assertStringContainsString($expected, $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('confirmTemplateSourceRetryAsksForConfirmationAndReturnsResultDataProvider')]
    public function confirmTemplateSourceRetryAsksForConfirmationAndReturnsResult(string $input, bool $expected): void
    {
        $exception = new Exception('Something went wrong.');

        $this->io->setUserInputs([$input]);

        self::assertSame($expected, $this->subject->confirmTemplateSourceRetry($exception));
        self::assertStringContainsString(
            implode(PHP_EOL, [
                'Something went wrong.',
                'You can go one step back and select another template provider.',
                'For more information, take a look at the documentation.',
                '',
                'Continue? [Y/n]',
            ]),
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function confirmProjectGenerationAsksForConfirmationAndReturnsResult(): void
    {
        $this->io->setUserInputs(['yes']);

        self::assertTrue($this->subject->confirmProjectRegeneration());
        self::assertStringContainsString(
            implode(PHP_EOL, [
                'If you want, you can restart project generation now.',
                'Restart? [Y/n]',
            ]),
            $this->io->getOutput(),
        );
    }

    #[Framework\Attributes\Test]
    public function confirmProjectGenerationAsksForRunCommandAndReturnsResult(): void
    {
        $this->io->setUserInputs(['yes']);

        $dummyCommand = 'foo --bar';

        self::assertTrue($this->subject->confirmRunCommand($dummyCommand));
        self::assertStringContainsString(
            implode(PHP_EOL, [
                sprintf(
                    'Preparing to run "%s" in the project dir.',
                    $dummyCommand,
                ),
                'Do you wish to run this command? [Y/n]',
            ]),
            $this->io->getOutput(),
        );
    }

    /**
     * @return Generator<string, array{Package\PackageInterface, string}>
     */
    public static function selectTemplateSourceReturnsSelectedTemplateSourceDataProvider(): Generator
    {
        $package = new Package\Package('foo/baz', '1.0.0', '1.0.0');

        $completePackage = new Package\CompletePackage('foo/baz', '1.0.0', '1.0.0');
        $completePackage->setDescription('foo baz');

        $completePackageWithoutDescription = clone $completePackage;
        $completePackageWithoutDescription->setDescription(null);

        $abandonedPackageWithoutDescription = clone $completePackage;
        $abandonedPackageWithoutDescription->setDescription(null);
        $abandonedPackageWithoutDescription->setAbandoned(true);

        $abandonedPackageWithReplacement = clone $completePackage;
        $abandonedPackageWithReplacement->setAbandoned('foo/bar');

        $abandonedPackageWithoutReplacement = clone $completePackage;
        $abandonedPackageWithoutReplacement->setAbandoned(true);

        yield 'package' => [$package, 'foo/baz'];
        yield 'complete package' => [$completePackage, 'foo baz (foo/baz)'];
        yield 'complete package without description' => [$completePackageWithoutDescription, 'foo/baz'];
        yield 'abandoned package without description' => [$abandonedPackageWithoutDescription, 'foo/baz <warning>Abandoned! No replacement was suggested.</warning>'];
        yield 'abandoned package without replacement' => [$abandonedPackageWithoutReplacement, 'foo baz (foo/baz) <warning>Abandoned! No replacement was suggested.</warning>'];
        yield 'abandoned package with replacement' => [$abandonedPackageWithReplacement, 'foo baz (foo/baz) <warning>Abandoned! Use foo/bar instead.</warning>'];
    }

    /**
     * @return Generator<string, array{string, bool}>
     */
    public static function confirmTemplateSourceRetryAsksForConfirmationAndReturnsResultDataProvider(): Generator
    {
        yield 'default' => ['', true];
        yield 'yes' => ['yes', true];
        yield 'no' => ['no', false];
    }
}
