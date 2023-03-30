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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use LogicException;
use Symfony\Component\Filesystem;

/**
 * CleanUpStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CleanUpStepTest extends Tests\ContainerAwareTestCase
{
    private Filesystem\Filesystem $filesystem;
    private Src\Builder\Generator\Step\CleanUpStep $subject;
    private Src\Builder\BuildResult $result;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem\Filesystem();
        $this->subject = new Src\Builder\Generator\Step\CleanUpStep($this->filesystem);
        $this->result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(
                self::$config,
                Src\Helper\FilesystemHelper::getNewTemporaryDirectory(),
            ),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function runCleansUpRemainingFilesInTargetDirectory(): void
    {
        $targetDirectory = $this->result->getInstructions()->getTargetDirectory();

        $this->filesystem->mkdir($targetDirectory.'/.build');
        $this->filesystem->touch($targetDirectory.'/foo');

        self::assertDirectoryExists($targetDirectory.'/.build');
        self::assertFileExists($targetDirectory.'/foo');

        $this->subject->run($this->result);

        self::assertDirectoryDoesNotExist($targetDirectory.'/.build');
        self::assertFileExists($targetDirectory.'/foo');
        self::assertTrue($this->result->isStepApplied('cleanUp'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revertThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1652955151);
        $this->expectExceptionMessage('A cleanup cannot be reverted since it\'s a destructive action');

        $this->subject->revert($this->result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function supportsReturnsFalse(): void
    {
        self::assertFalse($this->subject::supports('foo'));
    }
}
