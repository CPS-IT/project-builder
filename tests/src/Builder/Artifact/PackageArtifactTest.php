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
 * PackageArtifactTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PackageArtifactTest extends Framework\TestCase
{
    private Src\Builder\Artifact\PackageArtifact $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Artifact\PackageArtifact(
            'name',
            'version',
            'sourceReference',
            'sourceUrl',
            'distUrl',
        );
    }

    #[Framework\Attributes\Test]
    public function artifactIsJsonSerializable(): void
    {
        $expected = [
            'name' => $this->subject->name,
            'version' => $this->subject->version,
            'sourceReference' => $this->subject->sourceReference,
            'sourceUrl' => $this->subject->sourceUrl,
            'distUrl' => $this->subject->distUrl,
        ];

        self::assertJsonStringEqualsJsonString(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($this->subject, JSON_THROW_ON_ERROR),
        );
    }
}
