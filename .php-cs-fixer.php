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

$config = new PhpCsFixer\Config();
$config->getFinder()
    ->files()
    ->name('*.php')
    ->in(__DIR__)
    ->ignoreVCSIgnored(true)
    ->ignoreDotFiles(false)
;

$ruleset = new CPSIT\PhpCsFixerConfig\Rule\DefaultRuleset();
$ruleset->apply($config);

// Enable parallel runs (PHP-CS-Fixer >= v3.57)
if (class_exists(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::class)) {
    $config->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
}

return $config;
