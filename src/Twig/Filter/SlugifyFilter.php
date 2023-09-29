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

namespace CPSIT\ProjectBuilder\Twig\Filter;

use Cocur\Slugify;
use Webmozart\Assert;

/**
 * SlugifyFilter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SlugifyFilter implements TwigFilterInterface
{
    private const NAME = 'slugify';

    public function __construct(
        private readonly Slugify\Slugify $slugify,
    ) {}

    public function __invoke(mixed $input, string $separator = null): string
    {
        Assert\Assert::string($input);

        $options = [];

        if (null !== $separator) {
            $options['separator'] = $separator;
        }

        return $this->slugify->slugify($input, $options);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getOptions(): array
    {
        return [];
    }
}
