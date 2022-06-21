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
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework\TestCase;

/**
 * ChainedValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ChainedValidatorTest extends TestCase
{
    private Src\IO\Validator\ChainedValidator $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\IO\Validator\ChainedValidator([
            new Src\IO\Validator\EmailValidator(),
            new Src\IO\Validator\NotEmptyValidator(),
            new Tests\Fixtures\ModifyingValidator(),
        ]);
    }

    /**
     * @test
     */
    public function constructorAddsGivenValidators(): void
    {
        $emailValidator = new Src\IO\Validator\EmailValidator();
        $notEmptyValidator = new Src\IO\Validator\NotEmptyValidator();
        $urlValidator = new Src\IO\Validator\UrlValidator();

        self::assertTrue($this->subject->has($emailValidator));
        self::assertTrue($this->subject->has($notEmptyValidator));
        self::assertFalse($this->subject->has($urlValidator));
    }

    /**
     * @test
     */
    public function invokeInvokesAllValidators(): void
    {
        $this->expectException(Src\Exception\ValidationException::class);
        $this->expectExceptionCode(1653062543);
        $this->expectExceptionMessage('The given input must not be empty.');

        ($this->subject)(null);
    }

    /**
     * @test
     */
    public function invokeInvokesAllValidatorsAndReturnsModifiedInput(): void
    {
        $actual = ($this->subject)('foo@bar.de');

        self::assertSame('FOO@BAR.DE', $actual);
    }

    /**
     * @test
     */
    public function removeRemovesValidator(): void
    {
        $validator = new Src\IO\Validator\EmailValidator();

        self::assertTrue($this->subject->has($validator));
        self::assertFalse($this->subject->remove($validator)->has($validator));
    }

    /**
     * @test
     */
    public function getTypeReturnsType(): void
    {
        self::assertSame('chained', $this->subject::getType());
    }

    /**
     * @test
     */
    public function supportsReturnsFalse(): void
    {
        self::assertFalse($this->subject::supports('email'));
        self::assertFalse($this->subject::supports('notEmpty'));
        self::assertFalse($this->subject::supports('url'));
    }
}
