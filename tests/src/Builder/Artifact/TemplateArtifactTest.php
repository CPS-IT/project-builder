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

namespace CPSIT\ProjectBuilder\Tests\Builder\Artifact;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework;

use function json_encode;

/**
 * TemplateArtifactTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TemplateArtifactTest extends Framework\TestCase
{
    private Src\Builder\Artifact\TemplateArtifact $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Artifact\TemplateArtifact(
            'identifier',
            'hash',
            new Src\Builder\Artifact\PackageArtifact(
                'name',
                'version',
                'sourceReference',
                'sourceUrl',
                'distUrl',
            ),
            [
                'name' => 'name',
                'url' => 'url',
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function artifactIsJsonSerializable(): void
    {
        $expected = [
            'identifier' => $this->subject->identifier,
            'hash' => $this->subject->hash,
            'package' => $this->subject->package,
            'provider' => $this->subject->provider,
        ];

        self::assertJsonStringEqualsJsonString(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($this->subject, JSON_THROW_ON_ERROR),
        );
    }
}
