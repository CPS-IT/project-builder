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

namespace CPSIT\ProjectBuilder\DependencyInjection\CompilerPass;

use Symfony\Component\Config;
use Symfony\Component\DependencyInjection;

/**
 * Dumps the ContainerBuilder to a cache file so that it can be used by
 * debugging tools such as the debug:container console command.
 *
 * @author Ryan Weaver <ryan@thatsquality.com>
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal Only to be used for testing purposes
 *
 * @codeCoverageIgnore
 *
 * @see https://github.com/symfony/framework-bundle/blob/5.4/DependencyInjection/Compiler/ContainerBuilderDebugDumpPass.php
 */
final class ContainerBuilderDebugDumpPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    public function __construct(
        private string $cachePath,
    ) {
    }

    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $cache = new Config\ConfigCache($this->cachePath, true);
        if (!$cache->isFresh()) {
            $cache->write((new DependencyInjection\Dumper\XmlDumper($container))->dump(), $container->getResources());
        }
    }
}
