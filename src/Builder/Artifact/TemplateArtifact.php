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

namespace CPSIT\ProjectBuilder\Builder\Artifact;

use CPSIT\ProjectBuilder\Builder\BuildResult;

/**
 * TemplateArtifact.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 *
 * @extends Artifact<array{
 *     identifier: string,
 *     hash: string,
 *     package: PackageArtifact,
 *     provider: array{name: string, url: string}
 * }>
 */
final class TemplateArtifact extends Artifact
{
    public function __construct(
        private readonly BuildResult $buildResult,
    ) {}

    public function dump(): array
    {
        $config = $this->buildResult->getInstructions()->getConfig();
        $package = $config->getTemplateSource()->getPackage();
        $provider = $config->getTemplateSource()->getProvider();

        return [
            'identifier' => $config->getIdentifier(),
            'hash' => $config->buildHash(),
            'package' => new PackageArtifact($package),
            'provider' => [
                'name' => $provider::getName(),
                'url' => $provider->getUrl(),
            ],
        ];
    }
}
