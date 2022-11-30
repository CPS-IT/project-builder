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

namespace CPSIT\ProjectBuilder\Tests\Naming;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use Generator;
use UnhandledMatchError;
use Webmozart\Assert;

/**
 * NameVariantBuilderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class NameVariantBuilderTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\BuildInstructions $instructions;
    private Src\Naming\NameVariantBuilder $subject;

    protected function setUp(): void
    {
        $this->instructions = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo',
        );
        $this->subject = new Src\Naming\NameVariantBuilder($this->instructions);
    }

    /**
     * @test
     */
    public function createVariantThrowsExceptionIfGivenVariantIsUnsupported(): void
    {
        $this->expectException(UnhandledMatchError::class);

        /* @phpstan-ignore-next-line */
        $this->subject->createVariant('foo');
    }

    /**
     * @test
     */
    public function createShortVariantThrowsExceptionIfCustomerNameIsNotAvailable(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        $this->subject->createShortVariant();
    }

    /**
     * @test
     *
     * @dataProvider createShortVariantReturnsShortVariantDataProvider
     */
    public function createShortVariantReturnsShortVariant(string $customerName, ?string $projectName, string $expected): void
    {
        $this->instructions->addTemplateVariable('project', [
            'customer_name' => $customerName,
            'name' => $projectName,
        ]);

        self::assertSame($expected, $this->subject->createShortVariant());
    }

    /**
     * @test
     */
    public function createShortVariantRespectsGivenStringCase(): void
    {
        $this->instructions->addTemplateVariable('project.customer_name', 'foo bar');

        self::assertSame('FooBar', $this->subject->createShortVariant(Src\StringCase::UPPER_CAMEL));
    }

    /**
     * @test
     */
    public function createAbbreviationVariantThrowsExceptionIfCustomerNameIsNotAvailable(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        $this->subject->createAbbreviationVariant();
    }

    /**
     * @test
     *
     * @dataProvider createAbbreviationVariantReturnsAbbreviationVariantDataProvider
     */
    public function createAbbreviationVariantReturnsAbbreviationVariant(string $customerName, ?string $projectName, string $expected): void
    {
        $this->instructions->addTemplateVariable('project', [
            'customer_abbreviation' => $customerName,
            'name' => $projectName,
        ]);

        self::assertSame($expected, $this->subject->createAbbreviationVariant());
    }

    /**
     * @test
     */
    public function createAbbreviationVariantRespectsGivenStringCase(): void
    {
        $this->instructions->addTemplateVariable('project.customer_abbreviation', 'foo bar');

        self::assertSame('FooBar', $this->subject->createAbbreviationVariant(Src\StringCase::UPPER_CAMEL));
    }

    /**
     * @test
     */
    public function createFullVariantThrowsExceptionIfCustomerNameIsNotAvailable(): void
    {
        $this->expectException(Assert\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: NULL');

        $this->subject->createFullVariant();
    }

    /**
     * @test
     *
     * @dataProvider createFullVariantReturnsFullVariantDataProvider
     */
    public function createFullVariantReturnsFullVariant(string $customerName, ?string $projectName, string $expected): void
    {
        $this->instructions->addTemplateVariable('project', [
            'customer_name' => $customerName,
            'name' => $projectName,
        ]);

        self::assertSame($expected, $this->subject->createFullVariant());
    }

    /**
     * @test
     */
    public function createFullVariantRespectsGivenStringCase(): void
    {
        $this->instructions->addTemplateVariable('project', [
            'customer_name' => 'foo bar',
            'name' => 'bar',
        ]);

        self::assertSame('FooBarBar', $this->subject->createFullVariant(Src\StringCase::UPPER_CAMEL));
    }

    /**
     * @test
     *
     * @dataProvider isDefaultProjectNameReturnsTrueIfGivenProjectNameIsTheDefaultDataProvider
     */
    public function isDefaultProjectNameReturnsTrueIfGivenProjectNameIsTheDefault(?string $projectName, bool $expected): void
    {
        self::assertSame($expected, $this->subject::isDefaultProjectName($projectName));
    }

    /**
     * @return Generator<string, array{string, string|null, string}>
     */
    public function createShortVariantReturnsShortVariantDataProvider(): Generator
    {
        yield 'no project name' => ['foo', null, 'foo'];
        yield 'default project name' => ['foo', 'basic', 'foo'];
        yield 'custom project name' => ['foo', 'bar', 'bar'];
    }

    /**
     * @return Generator<string, array{string, string|null, string}>
     */
    public function createAbbreviationVariantReturnsAbbreviationVariantDataProvider(): Generator
    {
        yield 'no project name' => ['foo', null, 'foo'];
        yield 'default project name' => ['foo', 'basic', 'foo'];
        yield 'custom project name' => ['foo', 'bar', 'bar'];
    }

    /**
     * @return Generator<string, array{string, string|null, string}>
     */
    public function createFullVariantReturnsFullVariantDataProvider(): Generator
    {
        yield 'no project name' => ['foo', null, 'Foo'];
        yield 'default project name' => ['foo', 'basic', 'Foo'];
        yield 'custom project name' => ['foo', 'bar', 'Foo Bar'];
    }

    /**
     * @return Generator<string, array{string|null, bool}>
     */
    public function isDefaultProjectNameReturnsTrueIfGivenProjectNameIsTheDefaultDataProvider(): Generator
    {
        yield 'null' => [null, true];
        yield 'basic' => ['basic', true];
        yield 'something different' => ['foo', false];
    }
}
