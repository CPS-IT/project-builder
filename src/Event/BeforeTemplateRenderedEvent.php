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

namespace CPSIT\ProjectBuilder\Event;

use CPSIT\ProjectBuilder\Builder;
use Twig\Environment;

/**
 * BeforeTemplateRenderedEvent.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BeforeTemplateRenderedEvent
{
    private Environment $twig;
    private Builder\BuildInstructions $instructions;

    /**
     * @var array<string, mixed>
     */
    private array $variables;

    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(Environment $twig, Builder\BuildInstructions $instructions, array $variables)
    {
        $this->twig = $twig;
        $this->instructions = $instructions;
        $this->variables = $variables;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getInstructions(): Builder\BuildInstructions
    {
        return $this->instructions;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function setVariables(array $variables): self
    {
        $this->variables = $variables;

        return $this;
    }
}
