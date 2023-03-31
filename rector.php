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

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->skip([
        __DIR__.'/tests/src/Fixtures/Templates/*/vendor/*',

        AddLiteralSeparatorToNumberRector::class,
        AnnotationToAttributeRector::class => [
            __DIR__.'/src/Bootstrap.php',
            __DIR__.'/src/Builder/Config/ConfigFactory.php',
            __DIR__.'/src/Console/Simulation.php',
            __DIR__.'/src/DependencyInjection/CompilerPass/ContainerBuilderDebugDumpPass.php',
            __DIR__.'/src/DependencyInjection/CompilerPass/EventListenerPass.php',
            __DIR__.'/src/DependencyInjection/CompilerPass/FactoryServicesPass.php',
            __DIR__.'/src/DependencyInjection/CompilerPass/PublicServicePass.php',
            __DIR__.'/src/DependencyInjection/ContainerFactory.php',
            __DIR__.'/src/ProjectBuilderPlugin.php',
        ],
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        PHPUnitLevelSetList::UP_TO_PHPUNIT_100,
    ]);
};
