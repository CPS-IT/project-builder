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

namespace CPSIT\ProjectBuilder\Tests\Builder\Artifact\Migration;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework\TestCase;

/**
 * Version1679497137Test.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Version1679497137Test extends TestCase
{
    private Src\Builder\Artifact\Migration\Version1679497137 $subject;

    /**
     * @var array<string, mixed>
     */
    private array $artifact;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Artifact\Migration\Version1679497137();
        $this->artifact = [
            'artifact' => [
                'file' => 'foo',
            ],
        ];
    }

    /**
     * @test
     */
    public function migrateMigratesArtifactFileToArtifactPath(): void
    {
        $expected = [
            'artifact' => [
                'path' => 'foo',
            ],
        ];

        self::assertSame($expected, $this->subject->migrate($this->artifact));
    }
}
