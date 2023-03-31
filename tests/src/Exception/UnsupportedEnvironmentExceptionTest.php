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

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework;

/**
 * UnsupportedEnvironmentExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class UnsupportedEnvironmentExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function forOutdatedComposerInstallationReturnsExceptionForOutdatedComposerInstallation(): void
    {
        $actual = Src\Exception\UnsupportedEnvironmentException::forOutdatedComposerInstallation();

        self::assertSame(
            'Your global Composer installation is not up to date.'.PHP_EOL.
            'Make sure that you have at least Composer 2.1 installed.'.PHP_EOL.
            'Run `composer global update --lock` and then restart project creation.',
            $actual->getMessage(),
        );
        self::assertSame(1670607990, $actual->getCode());
    }
}
