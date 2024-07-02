<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2024 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Event;

use CPSIT\ProjectBuilder\Builder;

/**
 * BuildInstructionCollectedEvent.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildInstructionCollectedEvent
{
    public function __construct(
        private readonly Builder\Config\ValueObject\Property|Builder\Config\ValueObject\SubProperty $property,
        private readonly string $path,
        private mixed $value,
        private readonly Builder\BuildResult $buildResult,
    ) {}

    public function getProperty(): Builder\Config\ValueObject\Property|Builder\Config\ValueObject\SubProperty
    {
        return $this->property;
    }

    /**
     * @phpstan-assert-if-true Builder\Config\ValueObject\Property $this->getProperty()
     */
    public function isProperty(): bool
    {
        return $this->property instanceof Builder\Config\ValueObject\Property;
    }

    /**
     * @phpstan-assert-if-true Builder\Config\ValueObject\SubProperty $this->getProperty()
     */
    public function isSubProperty(): bool
    {
        return $this->property instanceof Builder\Config\ValueObject\SubProperty;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getBuildResult(): Builder\BuildResult
    {
        return $this->buildResult;
    }
}
