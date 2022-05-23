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
use CPSIT\ProjectBuilder\Exception;
use Twig\Environment;
use Twig\Loader;

/**
 * Renderer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Renderer
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(
        Builder\BuildInstructions $instructions,
        string $template,
        array $variables = []
    ): string {
        $mergedVariables = array_merge_recursive(
            $instructions->getTemplateVariables(),
            $variables,
            ['instructions' => $instructions]
        );

        if (!$this->twig->getLoader()->exists($template)) {
            throw Exception\TemplateRenderingException::forMissingTemplate($template);
        }

        return $this->twig->render($template, $mergedVariables);
    }

    /**
     * @psalm-immutable
     */
    public function withRootPath(string $rootPath): self
    {
        $loader = new Loader\FilesystemLoader([$rootPath]);

        $twig = clone $this->twig;
        $twig->setLoader($loader);

        return new self($twig);
    }

    /**
     * @psalm-immutable
     */
    public function withDefaultTemplate(string $template): self
    {
        $loader = new Loader\ArrayLoader(['default' => $template]);

        $twig = clone $this->twig;
        $twig->setLoader($loader);

        return new self($twig);
    }
}
