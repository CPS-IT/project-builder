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

namespace CPSIT\ProjectBuilder\Tests\Template\Provider;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * ProviderFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProviderFactoryTest extends Tests\ContainerAwareTestCase
{
    private Src\Template\Provider\ProviderFactory $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Template\Provider\ProviderFactory::class);
    }

    #[Framework\Attributes\Test]
    public function getThrowsExceptionIfNoProviderOfGivenTypeIsAvailable(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "foo" is not supported.');

        $this->subject->get('foo');
    }

    #[Framework\Attributes\Test]
    public function getReturnsProviderOfGivenType(): void
    {
        self::assertInstanceOf(
            Src\Template\Provider\ComposerProvider::class,
            $this->subject->get('composer'),
        );
        self::assertInstanceOf(
            Src\Template\Provider\PackagistProvider::class,
            $this->subject->get('packagist'),
        );
        self::assertInstanceOf(
            Src\Template\Provider\VcsProvider::class,
            $this->subject->get('vcs'),
        );
    }
}
