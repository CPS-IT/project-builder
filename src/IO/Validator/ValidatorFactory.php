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

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Exception;

/**
 * ValidatorFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class ValidatorFactory
{
    /**
     * @param list<class-string<ValidatorInterface>> $validators
     */
    public function __construct(
        private array $validators,
    ) {}

    public function get(Builder\Config\ValueObject\PropertyValidator $validator): ValidatorInterface
    {
        /** @var class-string<ValidatorInterface> $currentValidator */
        foreach ($this->validators as $currentValidator) {
            if (!$currentValidator::supports($validator->getType())) {
                continue;
            }

            return new $currentValidator($validator->getOptions());
        }

        throw Exception\UnsupportedTypeException::create($validator->getType());
    }

    /**
     * @param list<Builder\Config\ValueObject\PropertyValidator> $validators
     */
    public function getAll(array $validators): ChainedValidator
    {
        $chainedValidator = new ChainedValidator();

        foreach ($validators as $validator) {
            $chainedValidator->add($this->get($validator));
        }

        return $chainedValidator;
    }
}
