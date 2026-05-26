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

namespace CPSIT\ProjectBuilder\Tests\Fixtures;

use CPSIT\ProjectBuilder\Builder;

/**
 * DummyMigration.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyMigration extends Builder\Artifact\Migration\BaseMigration
{
    /**
     * @var array{}|array{0: non-empty-string, 1?: non-empty-string|null, 2?: mixed}
     */
    public array $remapArguments = [];

    public function migrate(array $artifact): array
    {
        if ([] !== $this->remapArguments) {
            $this->remapValue($artifact, ...$this->remapArguments);
        }

        return $artifact;
    }

    public static function getVersion(): int
    {
        // Make sure to always perform this migration
        return PHP_INT_MAX;
    }
}
