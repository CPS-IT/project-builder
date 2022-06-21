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

use ArrayObject;

/**
 * ArrayHelper.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArrayHelper
{
    /**
     * @param iterable<string, mixed> $subject
     *
     * @return mixed
     */
    public static function getValueByPath(iterable $subject, string $path)
    {
        $pathSegments = array_filter(explode('.', $path));
        $reference = &$subject;

        foreach ($pathSegments as $pathSegment) {
            if (!self::pathSegmentExists($reference, $pathSegment)) {
                return null;
            }

            $reference = &$reference[$pathSegment];
        }

        return $reference;
    }

    /**
     * @param iterable<string, mixed> $subject
     * @param mixed                   $value
     */
    public static function setValueByPath(iterable &$subject, string $path, $value): void
    {
        $pathSegments = array_filter(explode('.', $path));
        $reference = &$subject;

        foreach ($pathSegments as $pathSegment) {
            if (!self::pathSegmentExists($reference, $pathSegment)) {
                $reference[$pathSegment] = [];
            }

            $reference = &$reference[$pathSegment];
        }

        $reference = $value;
    }

    /**
     * @param mixed $subject
     */
    private static function pathSegmentExists($subject, string $pathSegment): bool
    {
        if (!is_array($subject) && !($subject instanceof ArrayObject)) {
            return false;
        }

        if (is_array($subject)) {
            return array_key_exists($pathSegment, $subject);
        }

        return isset($subject[$pathSegment]);
    }
}
