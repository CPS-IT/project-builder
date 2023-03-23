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

namespace CPSIT\ProjectBuilder\Tests\Twig\Filter;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Generator;
use Webmozart\Assert;

use function error_reporting;

/**
 * SlugifyFilterTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SlugifyFilterTest extends Tests\ContainerAwareTestCase
{
    private Src\Twig\Filter\SlugifyFilter $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Twig\Filter\SlugifyFilter::class);
        error_reporting(E_WARNING);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeThrowsAssertionErrorIfGivenInputIsNotAString(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        ($this->subject)(null);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invokeReturnsSlugForGivenInputDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeReturnsSlugForGivenInput(?string $separator, string $expected): void
    {
        $actual = ($this->subject)('foo bar', $separator);

        self::assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{string|null, string}>
     */
    public function invokeReturnsSlugForGivenInputDataProvider(): Generator
    {
        yield 'default separator' => [null, 'foo-bar'];
        yield 'custom separator' => ['_', 'foo_bar'];
    }
}
