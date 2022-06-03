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

namespace CPSIT\ProjectBuilder\Tests\DependencyInjection;

use CPSIT\ProjectBuilder\DependencyInjection;
use Psr\Http\Client;
use Symfony\Component\DependencyInjection as SymfonyDI;

return static function (
    SymfonyDI\Loader\Configurator\ContainerConfigurator $configurator,
    SymfonyDI\ContainerBuilder $container
): void {
    $container->addCompilerPass(new DependencyInjection\CompilerPass\PublicServicePass());

    $services = $configurator->services();
    $services->get(Client\ClientInterface::class)->synthetic();
};
