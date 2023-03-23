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

namespace CPSIT\ProjectBuilder\Tests\Error;

use Composer\IO;
use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use CuyZ\Valinor\Mapper;
use CuyZ\Valinor\MapperBuilder;
use Exception;
use Generator;
use Symfony\Component\Console;
use Throwable;

/**
 * ErrorHandlerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ErrorHandlerTest extends Tests\ContainerAwareTestCase
{
    private Src\Error\ErrorHandler $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Error\ErrorHandler(self::$container->get('app.messenger'));
    }

    /**
     * @param list<string> $expectedOutput
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('handleExceptionWritesFormattedErrorMessageDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function handleExceptionWritesFormattedErrorMessage(Throwable $exception, array $expectedOutput): void
    {
        $this->subject->handleException($exception);

        $output = self::$io->getOutput();

        foreach ($expectedOutput as $expected) {
            self::assertStringContainsString($expected, $output);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handleExceptionThrowsExceptionIfOutputIsVerbose(): void
    {
        $io = new IO\BufferIO('', Console\Output\OutputInterface::VERBOSITY_VERBOSE);
        $subject = new Src\Error\ErrorHandler(Src\IO\Messenger::create($io));
        $exception = new Exception();

        $this->expectExceptionObject($exception);

        $subject->handleException($exception);
    }

    /**
     * @return Generator<string, array{Throwable, list<string>}>
     */
    public function handleExceptionWritesFormattedErrorMessageDataProvider(): Generator
    {
        yield 'exception' => [
            new Exception('Something went wrong.'),
            [
                'Something went wrong.',
            ],
        ];
        yield 'exception with code' => [
            new Exception('Something went wrong.', 123),
            [
                'Something went wrong. [123]',
            ],
        ];
        yield 'exception with parent' => [
            new Exception('Something went wrong.', 0, new Exception('This caused the exception.')),
            [
                'Something went wrong.',
                'Caused by: This caused the exception.',
            ],
        ];
        yield 'exception with code and parent' => [
            new Exception('Something went wrong.', 123, new Exception('This caused the exception.')),
            [
                'Something went wrong. [123]',
                'Caused by: This caused the exception.',
            ],
        ];
        yield 'exception with parent and its code' => [
            new Exception('Something went wrong.', 0, new Exception('This caused the exception.', 456)),
            [
                'Something went wrong.',
                'Caused by: This caused the exception. [456]',
            ],
        ];
        yield 'exception with code and parent and its code' => [
            new Exception('Something went wrong.', 123, new Exception('This caused the exception.', 456)),
            [
                'Something went wrong. [123]',
                'Caused by: This caused the exception. [456]',
            ],
        ];

        try {
            $mapper = (new MapperBuilder())->mapper();
            $mapper->map('array{foo: string}', null);

            self::fail('No exception thrown. This should not happen.');
        } catch (Mapper\MappingError $error) {
            yield 'MappingError' => [
                $error,
                [
                    'Could not map type `array{foo: string}`',
                    '[1617193185]',
                    'Cannot be empty and must be filled with a value matching type `array{foo: string}`.',
                ],
            ];
        }
    }
}
