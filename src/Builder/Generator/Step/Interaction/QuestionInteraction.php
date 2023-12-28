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

namespace CPSIT\ProjectBuilder\Builder\Generator\Step\Interaction;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Twig;
use Symfony\Component\ExpressionLanguage;

/**
 * QuestionInteraction.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class QuestionInteraction implements InteractionInterface
{
    private const TYPE = 'question';

    public function __construct(
        private readonly ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        private readonly IO\InputReader $reader,
        private readonly Twig\Renderer $renderer,
    ) {
    }

    public function interact(
        Builder\Config\ValueObject\CustomizableInterface $subject,
        Builder\BuildInstructions $instructions,
    ): string|bool {
        [$yesValue, $noValue] = $this->processOptions($subject->getOptions(), $instructions);

        return $this->reader->ask(
            $subject->getName(),
            $yesValue,
            $noValue,
            (bool) $subject->getDefaultValue(),
        );
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }

    /**
     * @param list<Builder\Config\ValueObject\PropertyOption> $options
     *
     * @return array{string|true, string|false}
     */
    private function processOptions(array $options, Builder\BuildInstructions $instructions): array
    {
        $yesValue = true;
        $noValue = false;
        $matches = fn (string $condition, bool $selected): bool => (bool) $this->expressionLanguage->evaluate(
            $condition,
            [...$instructions->getTemplateVariables(), ...['selected' => $selected]],
        );

        foreach ($options as $option) {
            $value = $this->renderValue((string) $option->getValue(), $instructions);

            if ($option->hasCondition()) {
                $condition = $option->getCondition();
            } else {
                $condition = 'selected';
            }

            if ($matches($condition, true)) {
                $yesValue = $value;
            }

            if ($matches($condition, false)) {
                $noValue = $value;
            }
        }

        return [$yesValue, $noValue];
    }

    private function renderValue(string $value, Builder\BuildInstructions $instructions): string
    {
        $renderer = $this->renderer->withDefaultTemplate($value);

        return $renderer->render($instructions);
    }
}
