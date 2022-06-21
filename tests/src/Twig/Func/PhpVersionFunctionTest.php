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

namespace CPSIT\ProjectBuilder\Tests\Twig\Func;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Webmozart\Assert;

/**
 * PhpVersionFunctionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PhpVersionFunctionTest extends Tests\ContainerAwareTestCase
{
    private Src\Twig\Func\PhpVersionFunction $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Twig\Func\PhpVersionFunction::class);
    }

    /**
     * @test
     */
    public function invokeThrowsExceptionIfGivenBranchIsNull(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        ($this->subject)();
    }

    /**
     * @test
     */
    public function invokeReturnsAndCachesLatestStableVersionOfGivenBranch(): void
    {
        self::$mockHandler->append(self::createJsonResponse(['version' => '8.0.10']));
        self::$mockHandler->append(self::createJsonResponse(['version' => '8.1.4']));

        self::assertSame(2, self::$mockHandler->count());

        $actual = ($this->subject)('8.0');

        self::assertSame('8.0.10', $actual);
        self::assertSame(1, self::$mockHandler->count());

        $actual = ($this->subject)('8.0');

        self::assertSame('8.0.10', $actual);
        self::assertSame(1, self::$mockHandler->count());

        $actual = ($this->subject)('8.1');

        self::assertSame('8.1.4', $actual);
        self::assertSame(0, self::$mockHandler->count());
    }
}
