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

namespace CPSIT\ProjectBuilder\Twig;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Event;
use CPSIT\ProjectBuilder\Exception;
use Symfony\Component\EventDispatcher;
use Twig\Environment;
use Twig\Error;
use Twig\Loader;

use function array_replace_recursive;

/**
 * Renderer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Renderer
{
    private ?string $defaultTemplate = null;

    public function __construct(
        private Environment $twig,
        private readonly EventDispatcher\EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param array<string, mixed> $variables
     */
    public function render(
        Builder\BuildInstructions $instructions,
        string $template = null,
        array $variables = [],
    ): string {
        $mergedVariables = array_replace_recursive($instructions->getTemplateVariables(), $variables);
        $event = new Event\BeforeTemplateRenderedEvent($this->twig, $instructions, $mergedVariables);
        $template ??= $this->defaultTemplate;

        if (null === $template) {
            throw Exception\TemplateRenderingException::forUndefinedTemplate();
        }

        if (!$this->twig->getLoader()->exists($template)) {
            throw Exception\TemplateRenderingException::forMissingTemplate($template);
        }

        $this->eventDispatcher->dispatch($event);

        $mergedVariables = $event->getVariables();
        $mergedVariables['instructions'] = $instructions;

        return $this->twig->render($template, $mergedVariables);
    }

    public function canRender(string $template): bool
    {
        try {
            $this->twig->load($template);
        } catch (Error\Error) {
            return false;
        }

        return true;
    }

    /**
     * @psalm-immutable
     */
    public function withRootPath(string $rootPath): self
    {
        $twig = clone $this->twig;
        $twig->setLoader(new Loader\FilesystemLoader([$rootPath]));

        $clone = clone $this;
        $clone->twig = $twig;

        return $clone;
    }

    /**
     * @psalm-immutable
     */
    public function withDefaultTemplate(string $template): self
    {
        $twig = clone $this->twig;
        $twig->setLoader(new Loader\ArrayLoader(['default' => $template]));

        $clone = clone $this;
        $clone->twig = $twig;
        $clone->defaultTemplate = 'default';

        return $clone;
    }
}
