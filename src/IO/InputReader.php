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

namespace CPSIT\ProjectBuilder\IO;

use Composer\IO;
use CPSIT\ProjectBuilder\Exception;
use function is_string;

/**
 * InputReader.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InputReader
{
    private IO\IOInterface $io;

    public function __construct(IO\IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @throws Exception\IOException
     */
    public function staticValue(
        string $label,
        string $default = null,
        bool $required = false,
        Validator\ValidatorInterface $validator = null
    ): ?string {
        $label = Messenger::decorateLabel($label, $default, $required);
        $validator = $this->makeValidator($validator, $required);
        $answer = $this->io->askAndValidate($label, $validator, 3, $default);

        if (!is_string($answer) && null !== $answer) {
            return null;
        }

        if (is_string($answer) && '' !== trim($answer)) {
            return trim($answer);
        }

        if (is_string($default) && '' !== trim($default)) {
            return trim($default);
        }

        return null;
    }

    /**
     * @param list<string>     $choices
     * @param bool|string|null $default
     *
     * @throws Exception\IOException
     */
    public function choices(string $label, array $choices, $default = null, bool $required = false): string
    {
        if (!$required) {
            array_unshift($choices, '<info>No selection</info>');

            if (null !== $default) {
                ++$default;
            }
        }

        $default ??= array_key_first($choices);
        $label = Messenger::decorateLabel($label, $default);
        $answer = (int) $this->io->select($label, $choices, $default, 3);

        if (isset($choices[$answer])) {
            return $choices[$answer];
        }
        if (!empty($default) && isset($choices[$default])) {
            return $choices[$default];
        }

        throw Exception\ValidationException::create('No selection was made. Please try again.');
    }

    /**
     * @template TYes
     * @template TNo
     *
     * @param TYes $yesValue
     * @param TNo  $noValue
     *
     * @return TYes|TNo
     */
    public function ask(string $question, $yesValue = true, $noValue = false, bool $default = true)
    {
        $label = Messenger::decorateLabel($question, $default ? 'Y' : 'N', true, [$default ? 'n' : 'y']);

        if ($this->io->askConfirmation($label, $default)) {
            return $yesValue;
        }

        return $noValue;
    }

    private function makeValidator(
        Validator\ValidatorInterface $validator = null,
        bool $required = false
    ): Validator\ChainedValidator {
        $chainedValidator = new Validator\ChainedValidator();

        if ($required) {
            $notEmptyValidator = new Validator\NotEmptyValidator(['strict' => true]);
            $chainedValidator->add($notEmptyValidator);
        }

        if (null !== $validator) {
            $chainedValidator->add($validator);
        }

        return $chainedValidator;
    }
}
