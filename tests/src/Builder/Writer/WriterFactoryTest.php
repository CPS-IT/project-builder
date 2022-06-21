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

/**
 * WriterFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class WriterFactoryTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Writer\WriterFactory $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Writer\WriterFactory::class);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfFileIsNotSupported(): void
    {
        $subject = new Src\Builder\Writer\WriterFactory([]);

        $this->expectException(Src\Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1652800199);
        $this->expectExceptionMessage('The type "foo" is not supported.');

        $subject->get('foo');
    }

    /**
     * @test
     */
    public function getReturnsWriterForGivenFile(): void
    {
        self::assertInstanceOf(Src\Builder\Writer\GenericFileWriter::class, $this->subject->get('foo'));
        self::assertInstanceOf(Src\Builder\Writer\TemplateWriter::class, $this->subject->get('foo.twig'));
    }
}
