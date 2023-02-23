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

namespace CPSIT\ProjectBuilder\Builder\Writer;

use JsonSerializable;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function json_encode;

/**
 * JsonFileWriter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class JsonFileWriter
{
    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
    ) {
    }

    public function write(Finder\SplFileInfo $file, string|JsonSerializable $json): bool
    {
        if ($json instanceof JsonSerializable) {
            $json = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        $this->filesystem->dumpFile($file->getPathname(), $json);

        return $this->filesystem->exists($file->getPathname());
    }
}
