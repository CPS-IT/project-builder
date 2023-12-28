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

use function debug_backtrace;
use function sprintf;

/**
 * ShouldNotHappenException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ShouldNotHappenException extends Exception
{
    public static function create(): self
    {
        return new self(self::createMessageWithBacktrace(), 1654003369);
    }

    /**
     * @see https://github.com/rectorphp/rector-src/blob/v0.8.8/src/Exception/ShouldNotHappenException.php
     */
    private static function createMessageWithBacktrace(): string
    {
        $debugBacktrace = debug_backtrace();
        $class = $debugBacktrace[2]['class'] ?? null;
        $function = $debugBacktrace[2]['function'];
        $line = $debugBacktrace[1]['line'] ?? 0;

        $method = null !== $class ? ($class . '::' . $function) : $function;

        return sprintf('Sorry, this should not have happened. Error raised at "%s()" on line %d.', $method, $line);
    }
}
