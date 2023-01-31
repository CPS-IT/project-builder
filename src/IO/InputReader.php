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

use function array_filter;
use function array_key_first;
use function array_map;
use function is_array;
use function is_string;
use function trim;

/**
 * InputReader.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InputReader
{
    public function __construct(
        private IO\IOInterface $io,
    ) {
    }

    /**
     * @return ($required is true ? string : string|null)
     *
     * @throws Exception\IOException
     */
    public function staticValue(
        string $label,
        string $default = null,
        bool $required = false,
        Validator\ValidatorInterface $validator = null,
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

    public function hiddenValue(string $label): ?string
    {
        $label = Messenger::decorateLabel($label);

        return $this->io->askAndHideAnswer($label);
    }

    /**
     * @param list<string> $choices
     *
     * @return string|list<string>|null
     *
     * @phpstan-return ($multiple is true ? list<string> : string|null)
     *
     * @throws Exception\IOException
     */
    public function choices(
        string $label,
        array $choices,
        bool|string|null $default = null,
        bool $required = false,
        bool $multiple = false,
    ): string|array|null {
        $noSelectionIndex = null;

        if (!$required) {
            array_unshift($choices, '<info>No selection</info>');
            $noSelectionIndex = array_key_first($choices);
        }

        if (null === $default) {
            $default = (string) array_key_first($choices);
        }
        if (is_string($default) && '' === trim($default)) {
            $default = false;
        }

        $label = Messenger::decorateLabel($label, $default, $required, [], $multiple);
        $answer = $this->io->select($label, $choices, $default, 3, 'Value "%s" is invalid', $multiple);

        if (is_array($answer)) {
            return $this->parseMultipleAnswers($answer, $choices, $noSelectionIndex);
        }

        return $this->parseSingleAnswer((int) $answer, $choices, $noSelectionIndex);
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
    public function ask(string $question, mixed $yesValue = true, mixed $noValue = false, bool $default = true): mixed
    {
        $label = Messenger::decorateLabel($question, $default ? 'Y' : 'N', true, [$default ? 'n' : 'y']);

        if ($this->io->askConfirmation($label, $default)) {
            return $yesValue;
        }

        return $noValue;
    }

    /**
     * @param string[]     $answers
     * @param list<string> $choices
     *
     * @return list<string>
     */
    private function parseMultipleAnswers(array $answers, array $choices, int $noSelectionIndex = null): array
    {
        $selections = array_map(
            fn ($answer): string => $choices[(int) $answer],
            array_filter($answers, fn ($answer): bool => $noSelectionIndex !== (int) $answer),
        );

        // @codeCoverageIgnoreStart
        if ([] === $selections) {
            throw Exception\ValidationException::create('No selection was made. Please try again.');
        }
        // @codeCoverageIgnoreEnd

        return array_values($selections);
    }

    /**
     * @param list<string> $choices
     */
    private function parseSingleAnswer(int $answer, array $choices, int $noSelectionIndex = null): ?string
    {
        $selection = null;

        if ($noSelectionIndex === $answer) {
            return null;
        }

        if (isset($choices[$answer])) {
            $selection = $choices[$answer];
        }

        // @codeCoverageIgnoreStart
        if (null === $selection) {
            throw Exception\ValidationException::create('No selection was made. Please try again.');
        }
        // @codeCoverageIgnoreEnd

        return $selection;
    }

    private function makeValidator(
        Validator\ValidatorInterface $validator = null,
        bool $required = false,
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
