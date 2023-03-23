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

namespace CPSIT\ProjectBuilder\Tests\IO\Validator;

use CPSIT\ProjectBuilder as Src;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * CallbackValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CallbackValidatorTest extends TestCase
{
    private Src\IO\Validator\CallbackValidator $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\IO\Validator\CallbackValidator(['callback' => $this->validate(...)]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorThrowsExceptionIfNoCallbackIsGiven(): void
    {
        $this->expectExceptionObject(
            Src\Exception\MisconfiguredValidatorException::forUnexpectedOption($this->subject, 'callback'),
        );

        new Src\IO\Validator\CallbackValidator();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorThrowsExceptionIfInvalidCallbackIsGiven(): void
    {
        $this->expectExceptionObject(
            Src\Exception\MisconfiguredValidatorException::forUnexpectedOption($this->subject, 'callback'),
        );

        /* @phpstan-ignore-next-line */
        new Src\IO\Validator\CallbackValidator(['callback' => 'foo']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invokeCallsConfiguredCallbackDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCallsConfiguredCallback(?string $input, ?string $expected): void
    {
        $actual = ($this->subject)($input);

        self::assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{string|null, string|null}>
     */
    public function invokeCallsConfiguredCallbackDataProvider(): Generator
    {
        yield 'null' => [null, null];
        yield 'string' => ['string', 'input is string'];
    }

    public function validate(?string $input): ?string
    {
        if (null === $input) {
            return null;
        }

        return 'input is '.$input;
    }
}
