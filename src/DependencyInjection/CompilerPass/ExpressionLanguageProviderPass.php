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

namespace CPSIT\ProjectBuilder\DependencyInjection\CompilerPass;

use CPSIT\ProjectBuilder\Exception;
use Symfony\Component\DependencyInjection;

/**
 * ExpressionLanguageProviderPass.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @codeCoverageIgnore
 */
final class ExpressionLanguageProviderPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    public function __construct(
        private readonly string $tagName,
        private readonly string $expressionLanguageId,
    ) {
    }

    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $serviceIds = $container->findTaggedServiceIds($this->tagName);

        if (!$container->hasDefinition($this->expressionLanguageId)) {
            throw Exception\ShouldNotHappenException::create();
        }

        $expressionLanguageService = $container->findDefinition($this->expressionLanguageId);

        foreach ($serviceIds as $serviceId => $tags) {
            if ($container->hasDefinition($serviceId)) {
                $expressionLanguageService->addMethodCall('registerProvider', [
                    new DependencyInjection\Reference($serviceId),
                ]);
            }
        }
    }
}
