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
use CPSIT\ProjectBuilder\Tests;
use Generator;
use Webmozart\Assert;

/**
 * NameVariantFunctionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class NameVariantFunctionTest extends Tests\ContainerAwareTestCase
{
    private Src\Twig\Func\NameVariantFunction $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Twig\Func\NameVariantFunction();
    }

    /**
     * @test
     */
    public function invokeThrowsExceptionIfGivenVariantIsNull(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        ($this->subject)();
    }

    /**
     * @test
     */
    public function invokeThrowsExceptionIfGivenContextDoesNotContainBuildInstructions(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an instance of '.Src\Builder\BuildInstructions::class.'. Got: NULL');

        /* @phpstan-ignore-next-line */
        ($this->subject)([], 'foo');
    }

    /**
     * @test
     * @dataProvider invokeReturnsNameVariantDataProvider
     *
     * @param Src\Naming\NameVariant::* $variant
     * @param Src\StringCase::*|null    $case
     */
    public function invokeReturnsNameVariant(string $variant, ?string $case, ?string $expected): void
    {
        $instructions = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo'
        );
        $instructions->addTemplateVariable('project', [
            'customer_name' => 'Foo Customer',
            'customer_abbreviation' => 'foo',
            'name' => 'bar',
        ]);

        $actual = ($this->subject)(['instructions' => $instructions], $variant, $case);

        self::assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{Src\Naming\NameVariant::*, Src\StringCase::*|null, string}>
     */
    public function invokeReturnsNameVariantDataProvider(): Generator
    {
        yield 'abbreviation' => [Src\Naming\NameVariant::ABBREVIATION, null, 'bar'];
        yield 'short name' => [Src\Naming\NameVariant::SHORT_NAME, null, 'bar'];
        yield 'full name' => [Src\Naming\NameVariant::FULL_NAME, null, 'Foo Customer Bar'];
        yield 'with case' => [Src\Naming\NameVariant::FULL_NAME, Src\StringCase::LOWER_CAMEL, 'fooCustomerBar'];
    }
}
