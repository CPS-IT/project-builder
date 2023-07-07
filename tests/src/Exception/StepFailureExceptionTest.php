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
use Exception;
use PHPUnit\Framework;

/**
 * StepFailureExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StepFailureExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function createReturnsExceptionOnStepFailure(): void
    {
        $previous = new Exception();
        $actual = Src\Exception\StepFailureException::create('foo', $previous);

        self::assertSame('Running step "foo" failed. All applied steps were reverted.', $actual->getMessage());
        self::assertSame(1652954290, $actual->getCode());
        self::assertSame($previous, $actual->getPrevious());
    }
}
