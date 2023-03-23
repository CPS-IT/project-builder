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
use PHPUnit\Framework\TestCase;

/**
 * TemplateRenderingExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TemplateRenderingExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function forMissingTemplateReturnsExceptionForMissingTemplate(): void
    {
        $actual = Src\Exception\TemplateRenderingException::forMissingTemplate('foo');

        self::assertSame('A template with identifier "foo" does not exist.', $actual->getMessage());
        self::assertSame(1653901911, $actual->getCode());
    }

    /**
     * @test
     */
    public function forUndefinedTemplateReturnsExceptionForUndefinedTemplate(): void
    {
        $actual = Src\Exception\TemplateRenderingException::forUndefinedTemplate();

        self::assertSame('No template given. Please provide a valid template to be rendered.', $actual->getMessage());
        self::assertSame(1654701586, $actual->getCode());
    }
}
