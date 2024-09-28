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

namespace CPSIT\ProjectBuilder\Tests\Resource\Local;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Console;

use function dirname;
use function getenv;
use function putenv;

/**
 * ComposerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ComposerTest extends Tests\ContainerAwareTestCase
{
    private Src\Resource\Local\Composer $subject;
    private string $composerJson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Resource\Local\Composer::class);
        $this->composerJson = dirname(__DIR__, 2).'/Fixtures/Templates/yaml-template/composer.json';
    }

    #[Framework\Attributes\Test]
    public function installThrowsExceptionIfGivenFileDoesNotExist(): void
    {
        $this->expectException(Src\Exception\IOException::class);
        $this->expectExceptionCode(1653394006);
        $this->expectExceptionMessage('The file "foo" does not exist.');

        $this->subject->install('foo');
    }

    #[Framework\Attributes\Test]
    public function installInstallsComposerDependenciesFromGivenFile(): void
    {
        $output = new Console\Output\BufferedOutput();

        $actual = $this->subject->install($this->composerJson, false, $output);
        $actualOutput = $output->fetch();

        self::assertSame(0, $actual);
        self::assertStringContainsString('Nothing to install, update or remove', $actualOutput);
        self::assertStringNotContainsString('phpunit', $actualOutput);
    }

    #[Framework\Attributes\Test]
    public function installInstallsAllComposerDependenciesIncludingDevDependencies(): void
    {
        $output = new Console\Output\BufferedOutput();

        $actual = $this->subject->install($this->composerJson, true, $output);
        $actualOutput = $output->fetch();

        self::assertSame(0, $actual);
        self::assertStringContainsString('Updating dependencies', $actualOutput);
        self::assertStringContainsString('Installing phpunit/phpunit', $actualOutput);
    }

    #[Framework\Attributes\Test]
    public function installRestoresInitialComposerEnvironmentVariable(): void
    {
        putenv('COMPOSER=foo');

        $this->subject->install($this->composerJson);

        self::assertSame('foo', getenv('COMPOSER'));

        putenv('COMPOSER');
    }
}
