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

use function is_callable;

/**
 * CallbackValidator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @extends AbstractValidator<array{callback: callable(string|null): (string|null)}>
 */
final class CallbackValidator extends AbstractValidator
{
    private const TYPE = 'callback';

    protected static array $defaultOptions = [
        'callback' => null,
    ];

    /**
     * @param array{callback?: callable(string|null): (string|null)} $options
     *
     * @throws Exception\MisconfiguredValidatorException
     */
    public function __construct(array $options = [])
    {
        if (!is_callable($options['callback'] ?? null)) {
            throw Exception\MisconfiguredValidatorException::forUnexpectedOption($this, 'callback');
        }

        parent::__construct($options);
    }

    public function __invoke(?string $input): ?string
    {
        return $this->options['callback']($input);
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
