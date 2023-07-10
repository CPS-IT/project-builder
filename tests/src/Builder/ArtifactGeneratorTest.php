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

namespace CPSIT\ProjectBuilder\Tests\Builder;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Finder;

use function dirname;

/**
 * ArtifactGeneratorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArtifactGeneratorTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\ArtifactGenerator $subject;
    private Finder\SplFileInfo $artifactFile;
    private Src\Builder\BuildResult $buildResult;
    private Package\RootPackageInterface $rootPackage;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\ArtifactGenerator::class);
        $this->artifactFile = Src\Helper\FilesystemHelper::createFileObject(
            Src\Helper\FilesystemHelper::getNewTemporaryDirectory(),
            'build-artifact.json',
        );
        $this->buildResult = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(
                self::$container->get('app.config'),
                'foo',
            ),
        );
        $this->rootPackage = Src\Resource\Local\Composer::createComposer(dirname(__DIR__, 3))->getPackage();
    }

    #[Framework\Attributes\Test]
    public function buildGeneratesArtifact(): void
    {
        $writtenDirectory = $this->buildResult->getWrittenDirectory();
        $step = new Tests\Fixtures\DummyStep();
        $step->addProcessedFile(
            new Src\Resource\Local\ProcessedFile(
                Src\Helper\FilesystemHelper::createFileObject($writtenDirectory, 'foo.json'),
                Src\Helper\FilesystemHelper::createFileObject($writtenDirectory, 'baz.json'),
            ),
        );

        $this->buildResult->getInstructions()->addTemplateVariable('foo', 'baz');
        $this->buildResult->applyStep($step);

        $actual = $this->subject->build($this->artifactFile, $this->buildResult, $this->rootPackage);

        $expected = new Src\Builder\Artifact\Artifact(
            new Src\Builder\Artifact\BuildArtifact(
                Src\Builder\ArtifactGenerator::VERSION,
                'build-artifact.json',
                $actual->artifact->date,
            ),
            new Src\Builder\Artifact\TemplateArtifact(
                'test',
                $actual->template->hash,
                new Src\Builder\Artifact\PackageArtifact(
                    'foo/baz',
                    '1.0.0',
                    null,
                    null,
                    null,
                ),
                [
                    'type' => 'dummy',
                    'url' => 'https://www.example.com',
                ],
            ),
            new Src\Builder\Artifact\GeneratorArtifact(
                new Src\Builder\Artifact\PackageArtifact(
                    $this->rootPackage->getName(),
                    $this->rootPackage->getPrettyVersion(),
                    $this->rootPackage->getSourceReference(),
                    $this->rootPackage->getSourceUrl(),
                    $this->rootPackage->getDistUrl(),
                ),
                'composer',
            ),
            new Src\Builder\Artifact\ResultArtifact(
                [
                    'foo' => 'baz',
                ],
                [
                    [
                        'type' => 'dummy',
                        'applied' => true,
                    ],
                ],
                [
                    [
                        'source' => 'foo.json',
                        'target' => 'baz.json',
                    ],
                ],
            ),
        );

        self::assertEquals($expected, $actual);
    }
}
