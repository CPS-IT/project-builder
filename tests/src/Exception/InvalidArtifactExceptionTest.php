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
use PHPUnit\Framework;

/**
 * InvalidArtifactExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidArtifactExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function forFileReturnExceptionForFile(): void
    {
        $actual = Src\Exception\InvalidArtifactException::forFile('foo');

        self::assertSame('The artifact file "foo" is invalid and cannot be processed.', $actual->getMessage());
        self::assertSame(1677141460, $actual->getCode());
    }

    #[Framework\Attributes\Test]
    public function forPathReturnExceptionForPath(): void
    {
        $actual = Src\Exception\InvalidArtifactException::forPath('foo.baz');

        self::assertSame('Invalid value at "foo.baz" in artifact.', $actual->getMessage());
        self::assertSame(1677140440, $actual->getCode());
    }

    #[Framework\Attributes\Test]
    public function forInvalidVersionReturnExceptionForInvalidVersion(): void
    {
        $actual = Src\Exception\InvalidArtifactException::forInvalidVersion();

        self::assertSame('Unable to detect a valid artifact version.', $actual->getMessage());
        self::assertSame(1677141758, $actual->getCode());
    }

    #[Framework\Attributes\Test]
    public function forValidationErrorsReturnsExceptionForGivenValidationErrors(): void
    {
        $validationErrors = $this->generateValidationErrors();

        $actual = Src\Exception\InvalidArtifactException::forValidationErrors($validationErrors);

        self::assertSame(
            'The artifact does not match the build artifact schema.'.PHP_EOL.
            '  * Error at property path "/": The data (null) must match the type: object',
            $actual->getMessage(),
        );
        self::assertSame(1677601857, $actual->getCode());
    }

    private function generateValidationErrors(): JsonSchema\Errors\ValidationError
    {
        $schemaFile = dirname(__DIR__, 3).'/resources/build-artifact.schema.json';
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
