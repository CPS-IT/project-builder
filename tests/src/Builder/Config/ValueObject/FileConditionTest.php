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

use CPSIT\ProjectBuilder\Builder as Src;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage;

/**
 * FileConditionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FileConditionTest extends TestCase
{
    private Src\Config\ValueObject\FileCondition $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Config\ValueObject\FileCondition('foo', 'bar', 'target');
    }

    /**
     * @test
     */
    public function getPathReturnsPath(): void
    {
        self::assertSame('foo', $this->subject->getPath());
    }

    /**
     * @test
     */
    public function getConditionReturnsCondition(): void
    {
        self::assertSame('bar', $this->subject->getCondition());
    }

    /**
     * @test
     */
    public function hasConditionReturnsTrue(): void
    {
        self::assertTrue($this->subject->hasCondition());
    }

    /**
     * @test
     */
    public function conditionMatchesChecksIfConditionMatches(): void
    {
        $expressionLanguage = new ExpressionLanguage\ExpressionLanguage();

        self::assertFalse($this->subject->conditionMatches($expressionLanguage, ['bar' => false]));
        self::assertTrue($this->subject->conditionMatches($expressionLanguage, ['bar' => true]));
    }

    /**
     * @test
     */
    public function getTargetReturnsTarget(): void
    {
        self::assertSame('target', $this->subject->getTarget());
    }
}
