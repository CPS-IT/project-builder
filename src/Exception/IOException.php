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

use function sprintf;

/**
 * IOException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class IOException extends Exception
{
    public static function forMissingFile(string $file): self
    {
        return new self(
            sprintf('The file "%s" does not exist.', $file),
            1653394006,
        );
    }

    public static function forMissingDirectory(string $directory): self
    {
        return new self(
            sprintf('The directory "%s" does not exist.', $directory),
            1679559818,
        );
    }

    public static function forEmptyDirectory(string $directory): self
    {
        return new self(
            sprintf('The directory "%s" must not be empty.', $directory),
            1679559876,
        );
    }
}
