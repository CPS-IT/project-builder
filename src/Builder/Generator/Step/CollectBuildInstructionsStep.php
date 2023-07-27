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

namespace CPSIT\ProjectBuilder\Builder\Generator\Step;

use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Twig;
use Symfony\Component\ExpressionLanguage;

use function is_string;

/**
 * CollectBuildInstructionsStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CollectBuildInstructionsStep extends AbstractStep
{
    private const TYPE = 'collectBuildInstructions';

    public function __construct(
        private readonly ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        private readonly IO\Messenger $messenger,
        private readonly Interaction\InteractionFactory $interactionFactory,
        private readonly Twig\Renderer $renderer,
    ) {
        parent::__construct();
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $instructions = $buildResult->getInstructions();

        foreach ($instructions->getConfig()->getProperties() as $property) {
            if (!$property->conditionMatches($this->expressionLanguage, $instructions->getTemplateVariables(), true)) {
                // Apply NULL as value of property to avoid errors in conditions
                // that reference this property in array-notation
                $this->apply($property->getPath(), null, $buildResult);

                continue;
            }

            $this->messenger->section($property->getName());

            if ($property->hasValue() && $property->hasSubProperties()) {
                throw Exception\InvalidConfigurationException::forConflictingProperties('value', 'properties');
            }

            $this->applyProperty($property, $buildResult);
        }

        return true;
    }

    public function revert(Builder\BuildResult $buildResult): void
    {
        // Intentionally left blank.
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }

    private function applyProperty(Builder\Config\ValueObject\Property $property, Builder\BuildResult $buildResult): void
    {
        if ($property->hasValue()) {
            $this->apply(
                $property->getPath(),
                $this->renderValue($property->getValue(), $buildResult),
                $buildResult,
            );

            return;
        }

        foreach ($property->getSubProperties() as $subProperty) {
            $subProperty->setParent($property);
            $this->applySubProperty($subProperty, $buildResult);
        }
    }

    private function applySubProperty(Builder\Config\ValueObject\SubProperty $subProperty, Builder\BuildResult $buildResult): void
    {
        $instructions = $buildResult->getInstructions();

        if (!$subProperty->conditionMatches($this->expressionLanguage, $instructions->getTemplateVariables(), true)) {
            // Apply NULL as value of sub-property to avoid errors in conditions
            // that reference this sub-property in array-notation
            $this->apply($subProperty->getPath(), null, $buildResult);

            return;
        }

        if ($subProperty->hasValue()) {
            $this->apply($subProperty->getPath(), $subProperty->getValue(), $buildResult);

            return;
        }

        switch ($subProperty->getType()) {
            case 'dynamicSelect':
                $value = $this->processOptions($subProperty->getOptions(), $instructions);

                break;

            default:
                $interaction = $this->interactionFactory->get($subProperty->getType());
                $value = $interaction->interact($subProperty, $instructions);

                break;
        }

        $this->apply($subProperty->getPath(), $value, $buildResult);
    }

    private function apply(string $path, mixed $value, Builder\BuildResult $buildResult): void
    {
        $buildResult->getInstructions()->addTemplateVariable($path, $value);
        $buildResult->applyStep($this);
    }

    /**
     * @param list<Builder\Config\ValueObject\PropertyOption> $options
     */
    private function processOptions(array $options, Builder\BuildInstructions $instructions): mixed
    {
        foreach ($options as $option) {
            // A condition is required for dynamic selections
            if ($option->conditionMatches($this->expressionLanguage, $instructions->getTemplateVariables())) {
                return $option->getValue();
            }
        }

        return null;
    }

    private function renderValue(float|bool|int|string|null $value, Builder\BuildResult $buildResult): int|float|string|bool|null
    {
        if (!is_string($value)) {
            return $value;
        }

        return $this->renderer->withDefaultTemplate($value)->render($buildResult->getInstructions());
    }
}
