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

namespace CPSIT\ProjectBuilder\Exception;

use Opis\JsonSchema;

use function implode;
use function sprintf;

/**
 * InvalidConfigurationException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidConfigurationException extends Exception
{
    public static function create(string $identifier): self
    {
        return new self(
            sprintf('The config for "%s" does not exist or is not valid.', $identifier),
            1652952150,
        );
    }

    public static function forAmbiguousFiles(string $identifier, string $file): self
    {
        return new self(
            sprintf(
                'Configuration for "%s" already exists as "%s". Please use only one config file per template!',
                $identifier,
                $file,
            ),
            1652950002,
        );
    }

    public static function forFile(string $file): self
    {
        return new self(
            sprintf('The config file "%s" is invalid and cannot be processed.', $file),
            1652950625,
        );
    }

    public static function forSource(string $source): self
    {
        return new self(
            sprintf('The config source "%s" is invalid and cannot be processed.', mb_strimwidth($source, 0, 100, '…')),
            1653058480,
        );
    }

    public static function forConflictingProperties(string ...$properties): self
    {
        return new self(
            sprintf('Found conflicting properties "%s".', implode('", "', $properties)),
            1652956541,
        );
    }

    public static function forValidationErrors(?JsonSchema\Errors\ValidationError $error): self
    {
        $decoratedErrors = '';

        if (null !== $error) {
            $formatter = new JsonSchema\Errors\ErrorFormatter();
            /** @var array<string, string> $formattedErrors */
            $formattedErrors = $formatter->format($error, false);

            foreach ($formattedErrors as $path => $errorMessage) {
                $decoratedErrors .= PHP_EOL.sprintf('  * Error at property path "%s": %s', $path, $errorMessage);
            }
        }

        return new self(
            sprintf('The given config source does not match the config schema.%s', $decoratedErrors),
            1653303396,
        );
    }

    public static function forUnknownFile(string $identifier): self
    {
        return new self(
            sprintf('The config file for "%s" could not be determined.', $identifier),
            1653424186,
        );
    }

    public static function forUnknownTemplateSource(string $identifier): self
    {
        return new self(
            sprintf('The template source for "%s" could not be determined.', $identifier),
            1673458682,
        );
    }

    public static function forMissingManifestFile(string $file): self
    {
        return new self(
            sprintf('The Composer manifest file for "%s" could not be found.', $file),
            1664558700,
        );
    }
}
