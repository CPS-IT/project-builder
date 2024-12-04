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

use CPSIT\ProjectBuilder\Exception;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection;

use function class_exists;

/**
 * EventListenerPass.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @codeCoverageIgnore
 */
final class EventListenerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    public function __construct(
        private readonly string $tagName,
        private readonly string $dispatcherId,
    ) {}

    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds($this->tagName);

        if (!$container->hasDefinition($this->dispatcherId)) {
            throw Exception\ShouldNotHappenException::create();
        }

        $dispatcher = $container->findDefinition($this->dispatcherId);

        foreach ($services as $id => $tags) {
            if (!$container->hasDefinition($id) || ($service = $container->findDefinition($id))->isAbstract()) {
                continue;
            }

            /** @var class-string|null $className */
            $className = $service->getClass();

            if (null === $className) {
                throw Exception\ShouldNotHappenException::create();
            }

            /** @var array{method?: string, event?: class-string} $tag */
            foreach ($tags as $tag) {
                $method = $tag['method'] ?? '__invoke';
                $event = $tag['event'] ?? $this->determineEventFromClassMethod($className, $method);

                $dispatcher->addMethodCall(
                    'addListener',
                    [
                        $event,
                        [new DependencyInjection\Reference($id), $method],
                    ],
                );
            }
        }
    }

    /**
     * @param class-string $className
     *
     * @return class-string
     */
    private function determineEventFromClassMethod(string $className, string $methodName): string
    {
        $reflection = new ReflectionClass($className);

        if (!$reflection->hasMethod($methodName)) {
            throw Exception\ShouldNotHappenException::create();
        }

        $methodReflection = $reflection->getMethod($methodName);

        if ($methodReflection->getNumberOfRequiredParameters() < 1) {
            throw Exception\ShouldNotHappenException::create();
        }

        [$firstParameter] = $methodReflection->getParameters();
        $parameterType = $firstParameter->getType();

        if (!($parameterType instanceof ReflectionNamedType)) {
            throw Exception\ShouldNotHappenException::create();
        }

        $parameterClassName = $parameterType->getName();

        if (!class_exists($parameterClassName)) {
            throw Exception\ShouldNotHappenException::create();
        }

        return $parameterClassName;
    }
}
