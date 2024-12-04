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

namespace CPSIT\ProjectBuilder\Exception;

use Opis\JsonSchema;

use function sprintf;

/**
 * InvalidArtifactException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidArtifactException extends Exception
{
    public static function forFile(string $file): self
    {
        return new self(
            sprintf('The artifact file "%s" is invalid and cannot be processed.', $file),
            1677141460,
        );
    }

    public static function forPath(string $path): self
    {
        return new self(
            sprintf('Invalid value at "%s" in artifact.', $path),
            1677140440,
        );
    }

    public static function forInvalidVersion(): self
    {
        return new self('Unable to detect a valid artifact version.', 1677141758);
    }

    public static function forValidationErrors(?JsonSchema\Errors\ValidationError $errors): self
    {
        $decoratedErrors = '';

        if (null !== $errors) {
            $formatter = new JsonSchema\Errors\ErrorFormatter();
            /** @var array<string, string> $formattedErrors */
            $formattedErrors = $formatter->format($errors, false);

            foreach ($formattedErrors as $path => $errorMessage) {
                $decoratedErrors .= PHP_EOL.sprintf('  * Error at property path "%s": %s', $path, $errorMessage);
            }
        }

        return new self(
            sprintf('The artifact does not match the build artifact schema.%s', $decoratedErrors),
            1677601857,
        );
    }
}
