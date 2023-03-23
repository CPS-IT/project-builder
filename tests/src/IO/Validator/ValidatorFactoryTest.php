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

/**
 * ValidatorFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ValidatorFactoryTest extends Tests\ContainerAwareTestCase
{
    private Src\IO\Validator\ValidatorFactory $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\IO\Validator\ValidatorFactory::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "foo" is not supported.');

        $validator = new Src\Builder\Config\ValueObject\PropertyValidator('foo');

        $this->subject->get($validator);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getReturnsValidatorForGivenType(): void
    {
        $validator = new Src\Builder\Config\ValueObject\PropertyValidator('email');

        self::assertInstanceOf(Src\IO\Validator\EmailValidator::class, $this->subject->get($validator));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAllReturnsChainedValidator(): void
    {
        $emailValidator = new Src\Builder\Config\ValueObject\PropertyValidator('email');
        $notEmptyValidator = new Src\Builder\Config\ValueObject\PropertyValidator('notEmpty');

        $actual = $this->subject->getAll([$emailValidator, $notEmptyValidator]);

        self::assertTrue($actual->has(new Src\IO\Validator\EmailValidator()));
        self::assertTrue($actual->has(new Src\IO\Validator\NotEmptyValidator()));
        self::assertFalse($actual->has(new Src\IO\Validator\UrlValidator()));
    }
}
