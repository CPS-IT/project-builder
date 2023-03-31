<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Tests\Exception;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * InvalidTemplateSourceExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidTemplateSourceExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function forProviderReturnsExceptionForProvider(): void
    {
        $actual = Src\Exception\InvalidTemplateSourceException::forProvider(new Tests\Fixtures\DummyProvider());

        self::assertSame('The provider "dummy" does not provide any template sources.', $actual->getMessage());
        self::assertSame(1664557140, $actual->getCode());
    }

    #[Framework\Attributes\Test]
    public function forFailedInstallationReturnsExceptionForFailedInstallation(): void
    {
        $actual = Src\Exception\InvalidTemplateSourceException::forFailedInstallation($this->createTemplateSource());

        self::assertSame(
            'Installation of template source "cpsit/project-builder-template-json" from provider "dummy" failed.',
            $actual->getMessage(),
        );
        self::assertSame(1664557307, $actual->getCode());
    }

    #[Framework\Attributes\Test]
    public function forInvalidPackageVersionConstraintReturnsExceptionForInvalidPackageVersionConstraint(): void
    {
        $actual = Src\Exception\InvalidTemplateSourceException::forInvalidPackageVersionConstraint(
            $this->createTemplateSource(),
            'foo',
        );

        self::assertSame(
            'Unable to install template package "cpsit/project-builder-template-json" with version constraint "foo" using provider "dummy".',
            $actual->getMessage(),
        );
        self::assertSame(1671467692, $actual->getCode());
    }

    private function createTemplateSource(): Src\Template\TemplateSource
    {
        $sourcePath = dirname(__DIR__).'/Fixtures/Templates/json-template';
        $package = Src\Resource\Local\Composer::createComposer($sourcePath)->getPackage();

        self::assertInstanceOf(Package\Package::class, $package);

        $package->setSourceUrl($sourcePath);

        return new Src\Template\TemplateSource(new Tests\Fixtures\DummyProvider(), $package);
    }
}
