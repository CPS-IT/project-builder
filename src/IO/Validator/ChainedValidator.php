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

namespace CPSIT\ProjectBuilder\IO\Validator;

/**
 * ChainedValidator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ChainedValidator implements ValidatorInterface
{
    private const TYPE = 'chained';

    /**
     * @var array<string, ValidatorInterface>
     */
    private array $validators = [];

    /**
     * @param list<ValidatorInterface> $validators
     */
    public function __construct(array $validators = [])
    {
        foreach ($validators as $validator) {
            $this->add($validator);
        }
    }

    public function __invoke(?string $input): ?string
    {
        $result = $input;

        foreach ($this->validators as $validator) {
            $result = $validator($result);
        }

        return $result;
    }

    public function add(ValidatorInterface $validator): void
    {
        $this->validators[$validator::getType()] = $validator;
    }

    public function has(ValidatorInterface $validator): bool
    {
        return isset($this->validators[$validator::getType()]);
    }

    public function remove(ValidatorInterface $validator): self
    {
        unset($this->validators[$validator::getType()]);

        return $this;
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return false;
    }
}
