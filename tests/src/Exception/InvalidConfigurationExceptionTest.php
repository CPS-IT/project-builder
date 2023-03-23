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
use Opis\JsonSchema;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * InvalidConfigurationExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidConfigurationExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function createReturnsExceptionForInvalidConfiguration(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::create('foo');

        self::assertSame('The config for "foo" does not exist or is not valid.', $actual->getMessage());
        self::assertSame(1652952150, $actual->getCode());
    }

    /**
     * @test
     */
    public function forAmbiguousFilesReturnsExceptionForAmbiguousFiles(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forAmbiguousFiles('foo', 'baz');

        self::assertSame(
            'Configuration for "foo" already exists as "baz". Please use only one config file per template!',
            $actual->getMessage(),
        );
        self::assertSame(1652950002, $actual->getCode());
    }

    /**
     * @test
     */
    public function forFileReturnsExceptionForFile(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forFile('foo');

        self::assertSame('The config file "foo" is invalid and cannot be processed.', $actual->getMessage());
        self::assertSame(1652950625, $actual->getCode());
    }

    /**
     * @test
     */
    public function forSourceReturnsExceptionForSource(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forSource('foo');

        self::assertSame('The config source "foo" is invalid and cannot be processed.', $actual->getMessage());
        self::assertSame(1653058480, $actual->getCode());
    }

    /**
     * @test
     */
    public function forConflictingPropertiesReturnsExceptionForConflictingProperties(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forConflictingProperties('foo', 'baz', 'boo');

        self::assertSame('Found conflicting properties "foo", "baz", "boo".', $actual->getMessage());
        self::assertSame(1652956541, $actual->getCode());
    }

    /**
     * @test
     */
    public function forValidationErrorsReturnsExceptionWithoutValidationErrors(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forValidationErrors(null);

        self::assertSame('The given config source does not match the config schema.', $actual->getMessage());
        self::assertSame(1653303396, $actual->getCode());
    }

    /**
     * @test
     */
    public function forValidationErrorsReturnsExceptionForGivenValidationErrors(): void
    {
        $validationErrors = $this->generateValidationErrors();

        $actual = Src\Exception\InvalidConfigurationException::forValidationErrors($validationErrors);

        self::assertSame(
            'The given config source does not match the config schema.'.PHP_EOL.
            '  * Error at property path "/": The data (null) must match the type: object',
            $actual->getMessage(),
        );
        self::assertSame(1653303396, $actual->getCode());
    }

    /**
     * @test
     */
    public function forUnknownFileReturnsExceptionForUnknownFile(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forUnknownFile('foo');

        self::assertSame('The config file for "foo" could not be determined.', $actual->getMessage());
        self::assertSame(1653424186, $actual->getCode());
    }

    /**
     * @test
     */
    public function forUnknownTemplateSourceReturnsExceptionForUnknownTemplateSource(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forUnknownTemplateSource('foo');

        self::assertSame('The template source for "foo" could not be determined.', $actual->getMessage());
        self::assertSame(1673458682, $actual->getCode());
    }

    /**
     * @test
     */
    public function forMissingManifestFileReturnsExceptionForMissingManifestFile(): void
    {
        $actual = Src\Exception\InvalidConfigurationException::forMissingManifestFile('foo');

        self::assertSame('The Composer manifest file for "foo" could not be found.', $actual->getMessage());
        self::assertSame(1664558700, $actual->getCode());
    }

    private function generateValidationErrors(): JsonSchema\Errors\ValidationError
    {
        $schemaFile = dirname(__DIR__, 3).'/resources/config.schema.json';
        $schemaReference = 'file://'.$schemaFile;

        $resolver = new JsonSchema\Resolvers\SchemaResolver();
        $resolver->registerFile($schemaReference, $schemaFile);

        $validator = new JsonSchema\Validator();
        $validator->setResolver($resolver);

        $validationErrors = $validator->validate(null, $schemaReference)->error();

        self::assertInstanceOf(JsonSchema\Errors\ValidationError::class, $validationErrors);

        return $validationErrors;
    }
}
