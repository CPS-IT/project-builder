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

namespace CPSIT\ProjectBuilder\Builder\Artifact\Migration;

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Template;

/**
 * Migration1688738958.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Migration1688738958 extends BaseMigration
{
    public function __construct(
        private readonly Template\Provider\ProviderFactory $providerFactory,
    ) {
    }

    public function migrate(array $artifact): array
    {
        $this->remapValue(
            $artifact,
            'template.provider.name',
            'template.provider.type',
            fn (string $name) => $this->getProviderByName($name),
        );

        return $artifact;
    }

    public static function getSourceVersion(): int
    {
        return 1;
    }

    public static function getTargetVersion(): int
    {
        return 2;
    }

    /**
     * @throws Exception\UnknownTemplateProviderException
     */
    private function getProviderByName(string $name): string
    {
        foreach ($this->providerFactory->getAll() as $provider) {
            if ($name === $provider::getName()) {
                return $provider::getType();
            }
        }

        throw Exception\UnknownTemplateProviderException::create($name);
    }
}
