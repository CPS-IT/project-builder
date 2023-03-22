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
use PHPUnit\Framework\TestCase;

/**
 * ArtifactTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArtifactTest extends TestCase
{
    private Src\Builder\Artifact\BuildArtifact $buildArtifact;
    private Src\Builder\Artifact\TemplateArtifact $templateArtifact;
    private Src\Builder\Artifact\GeneratorArtifact $generatorArtifact;
    private Src\Builder\Artifact\ResultArtifact $resultArtifact;
    private Src\Builder\Artifact\Artifact $subject;

    protected function setUp(): void
    {
        $this->buildArtifact = new Src\Builder\Artifact\BuildArtifact(1, 'file', 123);
        $this->templateArtifact = new Src\Builder\Artifact\TemplateArtifact(
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
        $this->generatorArtifact = new Src\Builder\Artifact\GeneratorArtifact(
            new Src\Builder\Artifact\PackageArtifact(
                'name',
                'version',
                'sourceReference',
                'sourceUrl',
                'distUrl',
            ),
            'composer',
        );
        $this->resultArtifact = new Src\Builder\Artifact\ResultArtifact(
            [
                'foo' => 'foo',
                'baz' => 'baz',
            ],
            [
                [
                    'type' => 'type',
                    'applied' => true,
                ],
            ],
            [
                [
                    'source' => 'source',
                    'target' => 'target',
                ],
            ],
        );
        $this->subject = new Src\Builder\Artifact\Artifact(
            $this->buildArtifact,
            $this->templateArtifact,
            $this->generatorArtifact,
            $this->resultArtifact,
        );
    }

    /**
     * @test
     */
    public function dumpReturnsDumpedArtifact(): void
    {
        $expected = [
            'artifact' => $this->buildArtifact,
            'template' => $this->templateArtifact,
            'generator' => $this->generatorArtifact,
            'result' => $this->resultArtifact,
        ];

        self::assertSame($expected, $this->subject->dump());
    }
}
