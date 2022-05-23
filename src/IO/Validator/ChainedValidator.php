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

use Generator;

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
     * @var list<ValidatorInterface>
     */
    private array $validators;

    /**
     * @param list<ValidatorInterface> $validators
     */
    public function __construct(array $validators = [])
    {
        $this->validators = $validators;
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
        if (!$this->hasValidator($validator)) {
            $this->validators[] = $validator;
        }
    }

    public function remove(ValidatorInterface $validator): void
    {
        foreach ($this->searchValidator($validator) as $index => $currentValidator) {
            unset($this->validators[$index]);
        }
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return false;
    }

    /**
     * @return Generator<int, ValidatorInterface>
     */
    private function searchValidator(ValidatorInterface $validator): Generator
    {
        $type = $validator::getType();

        foreach ($this->validators as $index => $currentValidator) {
            if ($type === $currentValidator::getType()) {
                yield $index => $currentValidator;
            }
        }
    }

    private function hasValidator(ValidatorInterface $validator): bool
    {
        return [] !== iterator_to_array($this->searchValidator($validator));
    }
}
