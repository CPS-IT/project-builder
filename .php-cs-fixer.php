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

$config = new \PhpCsFixer\Config();

$config
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => true,
        ],
        'ordered_imports' => [
            'imports_order' => [
                'const',
                'class',
                'function',
            ],
        ],
    ])
;

$config->getFinder()
    ->files()
    ->name('*.php')
    ->in(__DIR__)
    ->ignoreVCSIgnored(true)
;

return $config;
