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
use DateTimeImmutable;
use DateTimeInterface;

use function is_numeric;
use function is_string;

/**
 * Version1679655901.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class Version1679655901 extends BaseVersion
{
    public function __construct(
        private readonly Template\Provider\ProviderFactory $providerFactory,
    ) {
    }

    public function migrate(array $artifact): array
    {
        $this->remapValue(
            $artifact,
            'artifact',
            'build',
        );

        $this->remapValue(
            $artifact,
            'build.version',
            newValue: self::getTargetVersion(),
        );

        $this->remapValue(
            $artifact,
            'template.provider.name',
            'template.provider.type',
            $this->migrateTemplateProviderName(...),
        );

        $this->remapValue(
            $artifact,
            'build.date',
            newValue: $this->migrateArtifactDate(...),
        );

        return $artifact;
    }

    /**
     * @throws Exception\InvalidArtifactException
     * @throws Exception\UnknownTemplateProviderException
     */
    private function migrateTemplateProviderName(mixed $providerName): string
    {
        if (!is_string($providerName)) {
            throw Exception\InvalidArtifactException::forPath('template.provider.name');
        }

        return $this->providerFactory->getByName($providerName)::getType();
    }

    /**
     * @throws \Exception
     */
    private function migrateArtifactDate(int|string $artifactDate): string
    {
        if (!is_numeric($artifactDate)) {
            return $artifactDate;
        }

        return (new DateTimeImmutable('@'.$artifactDate))->format(DateTimeInterface::ATOM);
    }

    public static function getSourceVersion(): int
    {
        return 1;
    }

    public static function getTargetVersion(): int
    {
        return 2;
    }
}
