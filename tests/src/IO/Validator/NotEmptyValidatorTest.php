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

namespace CPSIT\ProjectBuilder\Tests\IO\Validator;

use CPSIT\ProjectBuilder as Src;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * NotEmptyValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class NotEmptyValidatorTest extends TestCase
{
    private Src\IO\Validator\NotEmptyValidator $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\IO\Validator\NotEmptyValidator();
    }

    /**
     * @test
     * @dataProvider invokeThrowsExceptionIfGivenInputIsEmptyDataProvider
     */
    public function invokeThrowsExceptionIfGivenInputIsEmpty(?string $input): void
    {
        $this->expectException(Src\Exception\ValidationException::class);
        $this->expectExceptionCode(1653062543);
        $this->expectExceptionMessage('The given input must not be empty.');

        ($this->subject)($input);
    }

    /**
     * @test
     */
    public function invokeThrowsExceptionIfGivenInputIsAnEmptyStringAndStrictCheckIsEnabled(): void
    {
        $this->expectException(Src\Exception\ValidationException::class);
        $this->expectExceptionCode(1653062543);
        $this->expectExceptionMessage('The given input must not be an empty string.');

        $subject = new Src\IO\Validator\NotEmptyValidator(['strict' => true]);
        $subject('     ');
    }

    /**
     * @test
     */
    public function invokeDoesNotThrowExceptionIfGivenInputIsAnEmptyStringAndStrictCheckIsNotEnabled(): void
    {
        $actual = ($this->subject)('     ');

        self::assertSame('     ', $actual);
    }

    /**
     * @test
     */
    public function invokeReturnsGivenInputIfGivenInputIsValid(): void
    {
        $actual = ($this->subject)('foo');

        self::assertSame('foo', $actual);
    }

    /**
     * @return Generator<string, array{string|null}>
     */
    public function invokeThrowsExceptionIfGivenInputIsEmptyDataProvider(): Generator
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }
}
