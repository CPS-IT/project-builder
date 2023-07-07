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

namespace CPSIT\ProjectBuilder\DependencyInjection;

use Cocur\Slugify;
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Twig;
use GuzzleHttp\Client as GuzzleClient;
use Nyholm\Psr7;
use Opis\JsonSchema;
use Psr\Http\Client;
use Psr\Http\Message;
use SebastianFeldmann\Cli;
use Symfony\Component\DependencyInjection;
use Symfony\Component\EventDispatcher;
use Symfony\Component\ExpressionLanguage;
use Symfony\Component\Filesystem;
use Twig\Loader;

return static function (
    DependencyInjection\Loader\Configurator\ContainerConfigurator $configurator,
    DependencyInjection\ContainerBuilder $container,
): void {
    $container->registerForAutoconfiguration(Builder\Artifact\Migration\Migration::class)
        ->addTag('artifact.migration')
    ;
    $container->registerForAutoconfiguration(Builder\Writer\WriterInterface::class)
        ->addTag('builder.writer')
    ;
    $container->registerForAutoconfiguration(Builder\Generator\Step\Interaction\InteractionInterface::class)
        ->addTag('generator.interaction')
    ;
    $container->registerForAutoconfiguration(Builder\Generator\Step\StepInterface::class)
        ->addTag('generator.step')
    ;
    $container->registerForAutoconfiguration(IO\Validator\ValidatorInterface::class)
        ->addTag('io.validator')
    ;
    $container->registerForAutoconfiguration(Twig\Filter\TwigFilterInterface::class)
        ->addTag('twig.filter')
    ;
    $container->registerForAutoconfiguration(Twig\Func\TwigFunctionInterface::class)
        ->addTag('twig.function')
    ;

    $container->addCompilerPass(
        new CompilerPass\FactoryServicesPass(
            'io.validator',
            IO\Validator\ValidatorFactory::class,
            '$validators',
        ),
    );
    $container->addCompilerPass(
        new CompilerPass\EventListenerPass(
            'event.listener',
            EventDispatcher\EventDispatcherInterface::class,
        ),
    );

    $services = $configurator->services();

    // Add external services
    $services->set(ExpressionLanguage\ExpressionLanguage::class);
    $services->set(Filesystem\Filesystem::class);
    $services->set(JsonSchema\Validator::class);
    $services->set(Slugify\Slugify::class);
    $services->set(Client\ClientInterface::class, GuzzleClient::class);
    $services->set(Loader\LoaderInterface::class, Loader\FilesystemLoader::class);
    $services->set(Message\RequestFactoryInterface::class, Psr7\Factory\Psr17Factory::class);
    $services->set(Cli\Command\Runner::class, Cli\Command\Runner\Simple::class);
    $services->set(EventDispatcher\EventDispatcherInterface::class, EventDispatcher\EventDispatcher::class);
};
