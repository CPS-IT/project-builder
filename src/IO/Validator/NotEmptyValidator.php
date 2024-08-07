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

use CPSIT\ProjectBuilder\Exception;

/**
 * NotEmptyValidator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @extends AbstractValidator<array{strict: bool}>
 */
final class NotEmptyValidator extends AbstractValidator
{
    private const TYPE = 'notEmpty';

    protected static array $defaultOptions = [
        'strict' => false,
    ];

    public function __invoke(?string $input): string
    {
        if (null === $input || '' === $input) {
            throw Exception\ValidationException::create('The given input must not be empty.');
        }

        if ($this->isStrictCheckEnabled() && '' === trim($input)) {
            throw Exception\ValidationException::create('The given input must not be an empty string.');
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

    private function isStrictCheckEnabled(): bool
    {
        /* @phpstan-ignore-next-line cast.useless */
        return (bool) $this->options['strict'];
    }
}
