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

namespace CPSIT\ProjectBuilder\Console\IO;

use Composer\IO;
use CPSIT\ProjectBuilder\Exception;
use ReflectionClass;
use Symfony\Component\Console;

/**
 * AccessibleConsoleIO.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AccessibleConsoleIO extends IO\ConsoleIO
{
    public function __construct(
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
        ?Console\Helper\HelperSet $helperSet = null,
    ) {
        $helperSet ??= new Console\Helper\HelperSet([
            new Console\Helper\QuestionHelper(),
        ]);

        parent::__construct($input, $output, $helperSet);
    }

    /**
     * @throws Exception\ShouldNotHappenException
     */
    public static function fromIO(IO\IOInterface $io): self
    {
        if (!($io instanceof IO\ConsoleIO)) {
            return new self(
                new Console\Input\ArgvInput(),
                new Console\Output\ConsoleOutput(),
            );
        }

        return new self(
            self::getPropertyFromObject($io, 'input', Console\Input\InputInterface::class),
            self::getPropertyFromObject($io, 'output', Console\Output\OutputInterface::class),
            self::getPropertyFromObject($io, 'helperSet', Console\Helper\HelperSet::class),
        );
    }

    public function getInput(): Console\Input\InputInterface
    {
        return $this->input;
    }

    public function getOutput(): Console\Output\OutputInterface
    {
        return $this->output;
    }

    /**
     * @template T
     *
     * @param class-string<T> $expectedType
     *
     * @return T
     *
     * @throws Exception\ShouldNotHappenException
     */
    private static function getPropertyFromObject(
        object $object,
        string $propertyName,
        string $expectedType,
    ): mixed {
        $classReflection = new ReflectionClass($object);
        $propertyReflection = $classReflection->getProperty($propertyName);
        $value = $propertyReflection->getValue($object);

        if (!($value instanceof $expectedType)) {
            throw Exception\ShouldNotHappenException::create();
        }

        return $value;
    }
}
