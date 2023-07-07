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

namespace CPSIT\ProjectBuilder\Builder\Artifact\Migration;

use CPSIT\ProjectBuilder\Helper;

use function is_callable;

/**
 * BaseMigration.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class BaseMigration implements Migration
{
    /**
     * @param array<string, mixed>  $artifact
     * @param non-empty-string      $path
     * @param non-empty-string|null $targetPath
     */
    protected function remapValue(
        array &$artifact,
        string $path,
        string $targetPath = null,
        mixed $newValue = null,
    ): void {
        $currentValue = Helper\ArrayHelper::getValueByPath($artifact, $path);

        if (is_callable($newValue)) {
            $newValue = $newValue($currentValue);
        } elseif (null === $newValue) {
            $newValue = $currentValue;
        }

        if (null === $targetPath) {
            Helper\ArrayHelper::setValueByPath($artifact, $path, $newValue);
        } else {
            Helper\ArrayHelper::setValueByPath($artifact, $targetPath, $newValue);
            Helper\ArrayHelper::removeByPath($artifact, $path);
        }
    }
}
