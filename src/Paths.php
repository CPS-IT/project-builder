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
enum Paths
{
    /**
     * Paths of protected project files required for successful project generation.
     */
    final public const PROTECTED_PATHS = [
        '.build',
    ];

    /**
     * Path to template source installer.
     */
    final public const PROJECT_INSTALLER = 'installer';

    /**
     * Path to JSON schema for template config files.
     */
    final public const PROJECT_SCHEMA_CONFIG = 'resources/config.schema.json';

    /**
     * Internal reference to the JSON schema for template config files.
     */
    final public const PROJECT_SCHEMA_REFERENCE = 'https://project-builder.cps-it.de/schema.json';

    /**
     * Path to service configurations.
     */
    final public const PROJECT_SERVICE_CONFIG = 'config';

    /**
     * Path to project source files.
     */
    final public const PROJECT_SOURCES = 'src';

    /**
     * Path to project template root.
     */
    final public const PROJECT_TEMPLATES = '.build/templates';

    /**
     * Path to source files within template directories.
     */
    final public const TEMPLATE_SOURCES = 'templates/src';

    /**
     * Path to shared source files within template directories.
     */
    final public const TEMPLATE_SHARED_SOURCES = 'templates/shared';

    /**
     * Path to service configurations within template directories.
     */
    final public const TEMPLATE_SERVICE_CONFIG = 'config';

    /**
     * Path to JSON schema for build artifacts.
     */
    final public const BUILD_ARTIFACT_SCHEMA = 'resources/build-artifact.schema.json';
}
