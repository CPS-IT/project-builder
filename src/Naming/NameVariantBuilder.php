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

namespace CPSIT\ProjectBuilder\Naming;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\StringCase;
use Webmozart\Assert;

use function is_string;

/**
 * NameVariantBuilder.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class NameVariantBuilder
{
    public function __construct(
        private readonly Builder\BuildInstructions $instructions,
    ) {}

    /**
     * @param value-of<NameVariant>     $variant
     * @param value-of<StringCase>|null $case
     *
     * @throws Exception\StringConversionException
     */
    public function createVariant(string $variant, ?string $case = null): string
    {
        return match ($variant) {
            NameVariant::Abbreviation->value => $this->createAbbreviationVariant($case),
            NameVariant::ShortName->value => $this->createShortVariant($case),
            NameVariant::FullName->value => $this->createFullVariant($case),
        };
    }

    /**
     * @param value-of<StringCase>|null $case
     *
     * @throws Exception\StringConversionException
     */
    public function createShortVariant(?string $case = null): string
    {
        $customerName = $this->instructions->getTemplateVariable('project.customer_name');
        $projectName = $this->instructions->getTemplateVariable('project.name');

        Assert\Assert::string($customerName);

        if (!is_string($projectName) || self::isDefaultProjectName($projectName)) {
            $nameVariant = $customerName;
        } else {
            $nameVariant = $projectName;
        }

        if (null === $case) {
            return $nameVariant;
        }

        return Helper\StringHelper::convertCase(strtolower($nameVariant), $case);
    }

    /**
     * @param value-of<StringCase>|null $case
     *
     * @throws Exception\StringConversionException
     */
    public function createAbbreviationVariant(?string $case = null): string
    {
        $customerAbbreviation = $this->instructions->getTemplateVariable('project.customer_abbreviation');
        $projectName = $this->instructions->getTemplateVariable('project.name');

        Assert\Assert::string($customerAbbreviation);

        if (!is_string($projectName) || self::isDefaultProjectName($projectName)) {
            $nameVariant = $customerAbbreviation;
        } else {
            $nameVariant = $projectName;
        }

        if (null === $case) {
            return $nameVariant;
        }

        return Helper\StringHelper::convertCase(strtolower($nameVariant), $case);
    }

    /**
     * @param value-of<StringCase>|null $case
     *
     * @throws Exception\StringConversionException
     */
    public function createFullVariant(?string $case = null): string
    {
        $customerName = $this->instructions->getTemplateVariable('project.customer_name');
        $projectName = $this->instructions->getTemplateVariable('project.name');
        $components = [
            $customerName,
        ];

        Assert\Assert::string($customerName);

        if (is_string($projectName) && !self::isDefaultProjectName($projectName)) {
            $components[] = $projectName;
        }

        $nameVariant = ucwords(implode(' ', array_filter($components)));

        if (null === $case) {
            return $nameVariant;
        }

        return Helper\StringHelper::convertCase($nameVariant, $case);
    }

    public static function isDefaultProjectName(?string $projectName): bool
    {
        return null === $projectName || 'basic' === $projectName;
    }
}
