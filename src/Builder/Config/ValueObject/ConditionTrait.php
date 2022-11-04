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

namespace CPSIT\ProjectBuilder\Builder\Config\ValueObject;

use Symfony\Component\ExpressionLanguage;

/**
 * ConditionTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait ConditionTrait
{
    protected ?string $if = null;

    public function getCondition(): ?string
    {
        return $this->if;
    }

    /**
     * @phpstan-assert-if-true !null $this->getCondition()
     */
    public function hasCondition(): bool
    {
        return null !== $this->if;
    }

    /**
     * @param array<string, mixed> $additionalVariables
     */
    public function conditionMatches(
        ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        array $additionalVariables = [],
        bool $default = false
    ): bool {
        if (!$this->hasCondition()) {
            return $default;
        }

        $condition = $this->getCondition();

        return (bool) $expressionLanguage->evaluate($condition, $additionalVariables);
    }
}
