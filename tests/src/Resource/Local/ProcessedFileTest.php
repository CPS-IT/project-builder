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

namespace CPSIT\ProjectBuilder\Tests\Resource\Local;

use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder;
use function dirname;

/**
 * ProcessedFileTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessedFileTest extends TestCase
{
    private Finder\SplFileInfo $originalFile;
    private Finder\SplFileInfo $targetFile;
    private Src\Resource\Local\ProcessedFile $subject;

    protected function setUp(): void
    {
        $this->originalFile = new Finder\SplFileInfo(__FILE__, dirname(__FILE__), __FILE__);
        $this->targetFile = clone $this->originalFile;
        $this->subject = new Src\Resource\Local\ProcessedFile($this->originalFile, $this->targetFile);
    }

    /**
     * @test
     */
    public function getOriginalFileReturnsOriginalFile(): void
    {
        self::assertSame($this->originalFile, $this->subject->getOriginalFile());
    }

    /**
     * @test
     */
    public function getTargetFileReturnsTargetFile(): void
    {
        self::assertSame($this->targetFile, $this->subject->getTargetFile());
    }
}
