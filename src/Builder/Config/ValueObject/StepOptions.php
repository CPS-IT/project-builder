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

/**
 * StepOptions.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StepOptions
{
    /**
     * @param list<FileCondition> $fileConditions
     */
    public function __construct(
        private readonly array $fileConditions = [],
        private readonly ?string $templateFile = null,
        private readonly ?string $artifactPath = null,
        private readonly ?string $command = null,
        private readonly bool $skipConfirmation = false,
        private readonly bool $allowFailure = false,
        private readonly bool $required = true,
    ) {}

    /**
     * @return list<FileCondition>
     */
    public function getFileConditions(): array
    {
        return $this->fileConditions;
    }

    public function getTemplateFile(): ?string
    {
        return $this->templateFile;
    }

    public function getArtifactPath(): ?string
    {
        return $this->artifactPath;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function shouldSkipConfirmation(): bool
    {
        return $this->skipConfirmation;
    }

    public function shouldAllowFailure(): bool
    {
        return $this->allowFailure;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
