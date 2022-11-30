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

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Naming;
use CPSIT\ProjectBuilder\StringCase;
use Webmozart\Assert;

/**
 * NameVariantFunction.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class NameVariantFunction implements TwigFunctionInterface
{
    private const NAME = 'name_variant';

    /**
     * @param array<string, mixed>       $context
     * @param Naming\NameVariant::*|null $variant
     * @param StringCase::*|null         $case
     *
     * @throws Exception\StringConversionException
     */
    public function __invoke(array $context = [], string $variant = null, string $case = null): string
    {
        $instructions = $context['instructions'] ?? null;

        Assert\Assert::string($variant);
        Assert\Assert::isInstanceOf($instructions, Builder\BuildInstructions::class);

        $builder = new Naming\NameVariantBuilder($instructions);

        return $builder->createVariant($variant, $case);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getOptions(): array
    {
        return [
            'needs_context' => true,
        ];
    }
}
