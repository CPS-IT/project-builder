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
use function is_string;

/**
 * SelectInteraction.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SelectInteraction implements InteractionInterface
{
    private const TYPE = 'select';

    private ExpressionLanguage\ExpressionLanguage $expressionLanguage;
    private IO\InputReader $reader;
    private Twig\Renderer $renderer;

    public function __construct(
        ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        IO\InputReader $reader,
        Twig\Renderer $renderer
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->reader = $reader;
        $this->renderer = $renderer;
    }

    /**
     * @return string|list<string>|null
     */
    public function interact(
        Builder\Config\ValueObject\CustomizableInterface $subject,
        Builder\BuildInstructions $instructions
    ) {
        return $this->reader->choices(
            $subject->getName(),
            $this->processOptions($subject->getOptions(), $instructions),
            $this->renderValue($subject->getDefaultValue(), $instructions),
            $subject->isRequired(),
            $subject->canHaveMultipleValues()
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
     * @return list<string>
     */
    private function processOptions(array $options, Builder\BuildInstructions $instructions): array
    {
        $processedOptions = [];

        foreach ($options as $option) {
            if ($option->conditionMatches($this->expressionLanguage, $instructions->getTemplateVariables(), true)) {
                $processedOptions[] = $this->renderValue($option->getValue(), $instructions);
            }
        }

        return $processedOptions;
    }

    /**
     * @param mixed $value
     *
     * @phpstan-return ($value is string ? string : null)
     */
    private function renderValue($value, Builder\BuildInstructions $instructions): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $renderer = $this->renderer->withDefaultTemplate($value);

        return $renderer->render($instructions);
    }
}
