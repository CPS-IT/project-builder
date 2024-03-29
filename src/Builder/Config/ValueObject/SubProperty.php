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

use CPSIT\ProjectBuilder\IO\Validator\NotEmptyValidator;

/**
 * ConfigSubProperty.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SubProperty implements CustomizableInterface
{
    use ConditionTrait;
    use IdentifierTrait;
    use NameTrait;
    use TypeTrait;
    use ValueTrait;

    /**
     * @param list<PropertyOption>    $options
     * @param list<PropertyValidator> $validators
     */
    public function __construct(
        string $identifier,
        string $name,
        string $type,
        ?string $path = null,
        ?string $if = null,
        int|float|string|bool|null $value = null,
        private readonly array $options = [],
        private readonly bool $multiple = false,
        private readonly int|float|string|bool|null $defaultValue = null,
        private readonly array $validators = [],
        private ?Property $parent = null,
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->type = $type;
        $this->path = $path;
        $this->if = $if;
        $this->value = $value;
    }

    public function getPath(): string
    {
        if (null !== $this->path) {
            return $this->path;
        }

        $path = $this->identifier;

        if (null !== $this->parent) {
            $path = $this->parent->getPath().'.'.$path;
        }

        return $path;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function canHaveMultipleValues(): bool
    {
        return $this->multiple;
    }

    public function getDefaultValue(): int|float|string|bool|null
    {
        return $this->defaultValue;
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    public function isRequired(): bool
    {
        foreach ($this->validators as $validator) {
            if (NotEmptyValidator::getType() === $validator->getType()) {
                return true;
            }
        }

        return false;
    }

    public function getParent(): ?Property
    {
        return $this->parent;
    }

    public function setParent(Property $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
