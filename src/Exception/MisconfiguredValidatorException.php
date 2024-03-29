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

use CPSIT\ProjectBuilder\IO;
use Exception;

use function array_pop;
use function count;
use function implode;
use function sprintf;

/**
 * MisconfiguredValidatorException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MisconfiguredValidatorException extends Exception
{
    public static function forUnexpectedOption(
        string|IO\Validator\ValidatorInterface $validator,
        string $option,
    ): self {
        if ($validator instanceof IO\Validator\ValidatorInterface) {
            $validator = $validator::getType();
        }

        return new self(
            sprintf('The validator option "%s" of validator "%s" is invalid.', $option, $validator),
            1673886742,
        );
    }

    /**
     * @param list<string> $options
     */
    public static function forUnexpectedOptions(
        string|IO\Validator\ValidatorInterface $validator,
        array $options,
    ): self {
        if (1 === count($options)) {
            return self::forUnexpectedOption($validator, $options[0]);
        }

        if ($validator instanceof IO\Validator\ValidatorInterface) {
            $validator = $validator::getType();
        }

        $lastOption = array_pop($options);

        return new self(
            sprintf(
                'The validator options "%s" and "%s" of validator "%s" are invalid.',
                implode('", "', $options),
                $lastOption,
                $validator,
            ),
            1679253412,
        );
    }
}
