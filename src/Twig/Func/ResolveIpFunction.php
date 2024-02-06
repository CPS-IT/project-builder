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

namespace CPSIT\ProjectBuilder\Twig\Func;

use Webmozart\Assert;

use function gethostbynamel;
use function is_string;
use function parse_url;
use function str_contains;

/**
 * ResolveIpFunction.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ResolveIpFunction implements TwigFunctionInterface
{
    private const NAME = 'resolve_ip';

    public function __invoke(?string $hostname = null): ?string
    {
        Assert\Assert::string($hostname);

        if (str_contains($hostname, '://')) {
            $hostname = parse_url($hostname, PHP_URL_HOST);
        }

        if (!is_string($hostname)) {
            return null;
        }

        $ipAddresses = gethostbynamel($hostname);

        if (false === $ipAddresses || [] === $ipAddresses) {
            return null;
        }

        return reset($ipAddresses);
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
