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

namespace CPSIT\ProjectBuilder\IO\Validator;

use CPSIT\ProjectBuilder\Exception;

use function is_string;
use function preg_match;

/**
 * RegexValidator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @extends AbstractValidator<array{pattern: non-empty-string, errorMessage: non-empty-string}>
 */
final class RegexValidator extends AbstractValidator
{
    private const TYPE = 'regex';

    protected static array $defaultOptions = [
        'pattern' => '/.*/',
        'errorMessage' => 'The given input does not match the required pattern.',
    ];

    /**
     * @param array{pattern?: non-empty-string, errorMessage?: non-empty-string} $options
     */
    public function __construct(array $options = [])
    {
        if (!is_string($options['pattern'] ?? null)) {
            throw Exception\MisconfiguredValidatorException::forUnexpectedOption($this, 'pattern');
        }
        if (false === @preg_match($options['pattern'], '')) {
            throw Exception\MisconfiguredValidatorException::forUnexpectedOption($this, 'pattern');
        }

        parent::__construct($options);
    }

    public function __invoke(?string $input): ?string
    {
        if (1 !== preg_match($this->options['pattern'], (string) $input)) {
            throw Exception\ValidationException::create($this->options['errorMessage']);
        }

        return $input;
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }
}
