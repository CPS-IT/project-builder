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

namespace CPSIT\ProjectBuilder\Tests\Builder\Writer;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder;

use function basename;
use function dirname;

/**
 * GenericFileWriterTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GenericFileWriterTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Writer\GenericFileWriter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Writer\GenericFileWriter::class);
    }

    #[Framework\Attributes\Test]
    public function writeCopiesGivenFileToTemporaryDirectory(): void
    {
        $instructions = new Src\Builder\BuildInstructions(
            $this->container->get('app.config'),
            'foo',
        );
        $sourceFile = __FILE__;
        $file = new Finder\SplFileInfo($sourceFile, dirname($sourceFile), basename($sourceFile));

        $expected = $instructions->getTemporaryDirectory().'/'.$file->getRelativePathname();
        $actual = $this->subject->write($instructions, $file);

        self::assertSame($expected, $actual->getPathname());
        self::assertFileExists($expected);

        (new Filesystem\Filesystem())->remove(dirname($expected));
    }

    #[Framework\Attributes\Test]
    public function writeCopiesGivenFileToGivenTargetFile(): void
    {
        $instructions = new Src\Builder\BuildInstructions(
            $this->container->get('app.config'),
            'foo',
        );
        $sourceFile = __FILE__;
        $file = new Finder\SplFileInfo($sourceFile, dirname($sourceFile), basename($sourceFile));

        $expected = $instructions->getTemporaryDirectory().'/overrides/foo.php';
        $actual = $this->subject->write($instructions, $file, 'overrides/foo.php');

        self::assertSame($expected, $actual->getPathname());
        self::assertFileExists($expected);

        (new Filesystem\Filesystem())->remove(dirname($expected));
    }
}
