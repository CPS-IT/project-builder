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

namespace CPSIT\ProjectBuilder\Twig\Extension;

use CPSIT\ProjectBuilder\Twig;
use Twig\Extension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * ProjectBuilderExtension.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProjectBuilderExtension extends Extension\AbstractExtension
{
    /**
     * @var iterable<Twig\Filter\TwigFilterInterface>
     */
    private iterable $filters;

    /**
     * @var iterable<Twig\Func\TwigFunctionInterface>
     */
    private iterable $functions;

    /**
     * @param iterable<Twig\Filter\TwigFilterInterface> $filters
     * @param iterable<Twig\Func\TwigFunctionInterface> $functions
     */
    public function __construct(iterable $filters, iterable $functions)
    {
        $this->filters = $filters;
        $this->functions = $functions;
    }

    public function getFilters(): array
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            $filters[] = new TwigFilter($filter->getName(), $filter, $filter->getOptions());
        }

        return $filters;
    }

    public function getFunctions(): array
    {
        $functions = [];

        foreach ($this->functions as $function) {
            $functions[] = new TwigFunction($function->getName(), $function, $function->getOptions());
        }

        return $functions;
    }
}
