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

namespace CPSIT\ProjectBuilder;

/**
 * Paths.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class Paths
{
    /**
     * Paths of protected project files required for successful project generation.
     */
    public const PROTECTED_PATHS = [
        'vendor',
    ];

    /**
     * Path to JSON schema for template config files.
     */
    public const PROJECT_SCHEMA_CONFIG = 'resources/config.schema.json';

    /**
     * Path to service configurations.
     */
    public const PROJECT_SERVICE_CONFIG = 'config';

    /**
     * Path to project template root.
     */
    public const PROJECT_TEMPLATES = 'templates';

    /**
     * Path to source files within template directories.
     */
    public const TEMPLATE_SOURCES = 'src';

    /**
     * Path to shared source files within template directories.
     */
    public const TEMPLATE_SHARED_SOURCES = 'shared';

    /**
     * Path to service configurations within template directories.
     */
    public const TEMPLATE_SERVICE_CONFIG = 'config';
}
