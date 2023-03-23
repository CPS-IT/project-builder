<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
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

use CPSIT\ProjectBuilder\Console;
use CPSIT\ProjectBuilder\DependencyInjection;
use CPSIT\ProjectBuilder\IO;
use Symfony\Component\Console as SymfonyConsole;

$messenger = IO\Messenger::create(new \Composer\IO\NullIO());

$container = DependencyInjection\ContainerFactory::createForTesting()->get();
$container->set('app.messenger', $messenger);

$application = new SymfonyConsole\Application();
$application->add(Console\Command\CreateProjectCommand::create($messenger));
$application->add(Console\Command\SyncProjectCommand::create($messenger));

return $application;
