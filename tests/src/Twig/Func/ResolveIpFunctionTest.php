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
use Generator;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert;

/**
 * ResolveIpFunctionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ResolveIpFunctionTest extends TestCase
{
    private Src\Twig\Func\ResolveIpFunction $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Twig\Func\ResolveIpFunction();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeThrowsExceptionIfGivenHostnameIsNull(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        ($this->subject)();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeReturnsNullIfUrlCannotBeResolved(): void
    {
        $actual = ($this->subject)('https://');

        self::assertNull($actual);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeReturnsNullIfIpAddressCannotBeResolved(): void
    {
        $actual = ($this->subject)('foo.bar');

        self::assertNull($actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invokeReturnsResolvedIpAddressDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeReturnsResolvedIpAddress(string $hostname): void
    {
        $actual = ($this->subject)($hostname);

        self::assertIsString($actual);
        self::assertMatchesRegularExpression('/^\d{1,3}(\.\d{1,3}){3}$/', $actual);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function invokeReturnsResolvedIpAddressDataProvider(): Generator
    {
        yield 'hostname' => ['www.example.com'];
        yield 'url' => ['https://www.example.com'];
    }
}
