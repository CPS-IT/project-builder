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

namespace CPSIT\ProjectBuilder\Builder\Config;

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Template;

use function serialize;
use function sha1;

/**
 * Config.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Config
{
    private ?string $declaringFile = null;
    private ?Template\TemplateSource $templateSource = null;

    /**
     * @param non-empty-list<ValueObject\Step> $steps
     * @param list<ValueObject\Property>       $properties
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $name,
        private readonly array $steps,
        private readonly array $properties = [],
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-list<ValueObject\Step>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return list<ValueObject\Property>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getDeclaringFile(): string
    {
        if (null === $this->declaringFile) {
            throw Exception\InvalidConfigurationException::forUnknownFile($this->identifier);
        }

        return $this->declaringFile;
    }

    public function setDeclaringFile(?string $declaringFile): self
    {
        $this->declaringFile = $declaringFile;

        return $this;
    }

    public function getTemplateSource(): Template\TemplateSource
    {
        if (null === $this->templateSource) {
            throw Exception\InvalidConfigurationException::forUnknownTemplateSource($this->identifier);
        }

        return $this->templateSource;
    }

    public function setTemplateSource(Template\TemplateSource $templateSource): self
    {
        $this->templateSource = $templateSource;

        return $this;
    }

    /**
     * @internal
     */
    public function buildHash(): string
    {
        return sha1(
            serialize([
                'identifier' => $this->identifier,
                'name' => $this->name,
                'steps' => $this->steps,
                'properties' => $this->properties,
            ]),
        );
    }
}
