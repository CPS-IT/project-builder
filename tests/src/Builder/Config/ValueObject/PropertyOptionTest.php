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

namespace CPSIT\ProjectBuilder\Tests\Builder\Config\ValueObject;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage;

/**
 * PropertyOptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PropertyOptionTest extends TestCase
{
    private Src\Builder\Config\ValueObject\PropertyOption $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Config\ValueObject\PropertyOption('foo', 'bar');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getValueReturnsValue(): void
    {
        self::assertSame('foo', $this->subject->getValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function conditionMatchesReturnsDefaultIfNoConditionIsSet(): void
    {
        $expressionLanguage = new ExpressionLanguage\ExpressionLanguage();
        $subject = new Src\Builder\Config\ValueObject\PropertyOption('foo');

        self::assertTrue($subject->conditionMatches($expressionLanguage, [], true));
    }
}
