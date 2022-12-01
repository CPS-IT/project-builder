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

namespace CPSIT\ProjectBuilder\Builder;

use ArrayObject;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\Paths;
use Symfony\Component\Filesystem;

use function dirname;

/**
 * BuildInstructions.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @extends \ArrayObject<string, mixed>
 */
final class BuildInstructions extends ArrayObject
{
    private string $temporaryDirectory;

    public function __construct(
        private Config\Config $config,
        private string $targetDirectory,
    ) {
        parent::__construct();
        $this->temporaryDirectory = Helper\FilesystemHelper::getNewTemporaryDirectory();
    }

    public function getConfig(): Config\Config
    {
        return $this->config;
    }

    public function getTemplateDirectory(): string
    {
        return dirname($this->config->getDeclaringFile());
    }

    public function getSourceDirectory(): string
    {
        return Filesystem\Path::join($this->getTemplateDirectory(), Paths::TEMPLATE_SOURCES);
    }

    public function getSharedSourceDirectory(): string
    {
        return Filesystem\Path::join($this->getTemplateDirectory(), Paths::TEMPLATE_SHARED_SOURCES);
    }

    public function getTemporaryDirectory(): string
    {
        return $this->temporaryDirectory;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateVariables(): array
    {
        return (array) $this;
    }

    public function getTemplateVariable(string $path): mixed
    {
        return Helper\ArrayHelper::getValueByPath($this, $path);
    }

    public function addTemplateVariable(string $path, mixed $value): void
    {
        Helper\ArrayHelper::setValueByPath($this, $path, $value);
    }
}
