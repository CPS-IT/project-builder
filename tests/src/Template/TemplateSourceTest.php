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

namespace CPSIT\ProjectBuilder\Tests\Template;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * TemplateSourceTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TemplateSourceTest extends TestCase
{
    private Tests\Fixtures\DummyProvider $provider;
    private Package\PackageInterface $package;
    private Src\Template\TemplateSource $subject;

    protected function setUp(): void
    {
        $this->provider = new Tests\Fixtures\DummyProvider();
        $this->package = Src\Resource\Local\Composer::createComposer(Src\Helper\FilesystemHelper::getPackageDirectory())->getPackage();
        $this->subject = new Src\Template\TemplateSource($this->provider, $this->package);
    }

    /**
     * @test
     */
    public function getProviderReturnsProvider(): void
    {
        self::assertSame($this->provider, $this->subject->getProvider());
    }

    /**
     * @test
     */
    public function getPackageReturnsPackage(): void
    {
        self::assertSame($this->package, $this->subject->getPackage());
    }

    /**
     * @test
     */
    public function setPackageAppliesGivenPackage(): void
    {
        $newPackage = clone $this->package;

        self::assertSame($newPackage, $this->subject->setPackage($newPackage)->getPackage());
    }

    /**
     * @test
     */
    public function shouldUseDynamicVersionConstraintReturnsFalseInitially(): void
    {
        self::assertFalse($this->subject->shouldUseDynamicVersionConstraint());
    }

    /**
     * @test
     *
     * @dataProvider useDynamicVersionConstraintDefinesWhetherToUseDynamicVersionConstraintDataProvider
     */
    public function useDynamicVersionConstraintDefinesWhetherToUseDynamicVersionConstraint(
        bool $useDynamicVersionConstraint,
        bool $expected,
    ): void {
        $this->subject->useDynamicVersionConstraint($useDynamicVersionConstraint);

        self::assertSame($expected, $this->subject->shouldUseDynamicVersionConstraint());
    }

    /**
     * @return Generator<string, array{bool, bool}>
     */
    public function useDynamicVersionConstraintDefinesWhetherToUseDynamicVersionConstraintDataProvider(): Generator
    {
        yield 'true' => [true, true];
        yield 'false' => [false, false];
    }
}
