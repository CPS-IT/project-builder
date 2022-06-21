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

namespace CPSIT\ProjectBuilder\Builder\Config\ValueObject;

/**
 * ConfigProperty.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Property
{
    use IdentifierTrait;
    use NameTrait;
    use ValueTrait;

    /**
     * @var list<SubProperty>
     */
    private array $properties;

    /**
     * @param mixed             $value
     * @param list<SubProperty> $properties
     */
    public function __construct(
        string $identifier,
        string $name,
        string $path = null,
        $value = null,
        array $properties = []
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->path = $path;
        $this->value = $value;
        $this->properties = $properties;
    }

    /**
     * @return list<SubProperty>
     */
    public function getSubProperties(): array
    {
        return $this->properties;
    }

    public function hasSubProperties(): bool
    {
        return [] !== $this->properties;
    }
}
