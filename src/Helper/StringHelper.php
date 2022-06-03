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

namespace CPSIT\ProjectBuilder\Helper;

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\StringCase;

/**
 * StringHelper.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StringHelper
{
    private const REGEX_WORDS = '/[A-Z]{2,}(?=[A-Z][a-z]+\d*|\b)|[A-Z]?[a-z]+\d*|[A-Z]|\d+/';

    /**
     * @param StringCase::* $case
     *
     * @throws Exception\StringConversionException
     * @throws Exception\UnsupportedTypeException
     */
    public static function convertCase(string $string, string $case): string
    {
        switch ($case) {
            case StringCase::LOWER:
                return strtolower($string);

            case StringCase::UPPER:
                return strtoupper($string);

            case StringCase::SNAKE:
                return strtolower(implode('_', self::splitStringIntoChunks($string)));

            case StringCase::UPPER_CAMEL:
                return str_replace(' ', '', ucwords($string));

            case StringCase::LOWER_CAMEL:
                return str_replace(' ', '', lcfirst(ucwords($string)));
        }

        throw Exception\UnsupportedTypeException::create($case);
    }

    /**
     * @param array<string, mixed> $replacePairs
     */
    public static function interpolate(string $string, array $replacePairs): string
    {
        foreach (array_keys($replacePairs) as $replaceKey) {
            $replacePairs['{'.trim($replaceKey, '{}').'}'] = $replacePairs[$replaceKey];
            unset($replacePairs[$replaceKey]);
        }

        return strtr($string, $replacePairs);
    }

    /**
     * @return list<string>
     *
     * @see https://www.geeksforgeeks.org/how-to-convert-a-string-to-snake-case-using-javascript/
     */
    private static function splitStringIntoChunks(string $string): array
    {
        if (false === preg_match_all(self::REGEX_WORDS, $string, $matches)) {
            throw Exception\StringConversionException::forUnmatchedString($string);
        }

        return $matches[0];
    }
}
