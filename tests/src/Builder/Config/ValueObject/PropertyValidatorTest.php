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

/**
 * PropertyValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PropertyValidatorTest extends TestCase
{
    private Src\Config\ValueObject\PropertyValidator $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Config\ValueObject\PropertyValidator('foo', ['bar' => 'bar']);
    }

    /**
     * @test
     */
    public function getOptionsReturnsOptions(): void
    {
        self::assertSame(['bar' => 'bar'], $this->subject->getOptions());
    }

    /**
     * @test
     */
    public function getTypeReturnsType(): void
    {
        self::assertSame('foo', $this->subject->getType());
    }
}
