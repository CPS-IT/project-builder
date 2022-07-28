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
use Symfony\Component\ExpressionLanguage;

/**
 * CollectBuildInstructionsStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CollectBuildInstructionsStep extends AbstractStep
{
    private const TYPE = 'collectBuildInstructions';

    private ExpressionLanguage\ExpressionLanguage $expressionLanguage;
    private IO\Messenger $messenger;
    private Interaction\InteractionFactory $interactionFactory;

    public function __construct(
        ExpressionLanguage\ExpressionLanguage $expressionLanguage,
        IO\Messenger $messenger,
        Interaction\InteractionFactory $interactionFactory
    ) {
        parent::__construct();
        $this->expressionLanguage = $expressionLanguage;
        $this->messenger = $messenger;
        $this->interactionFactory = $interactionFactory;
    }

    public function run(Builder\BuildResult $buildResult): bool
    {
        $instructions = $buildResult->getInstructions();

        foreach ($instructions->getConfig()->getProperties() as $property) {
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
            $this->apply($property->getPath(), $property->getValue(), $buildResult);

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

    /**
     * @param mixed $value
     */
    private function apply(string $path, $value, Builder\BuildResult $buildResult): void
    {
        $buildResult->getInstructions()->addTemplateVariable($path, $value);
        $buildResult->applyStep($this);
    }

    /**
     * @param list<Builder\Config\ValueObject\PropertyOption> $options
     *
     * @return int|float|string|null
     */
    private function processOptions(array $options, Builder\BuildInstructions $instructions)
    {
        foreach ($options as $option) {
            // A condition is required for dynamic selections
            if ($option->conditionMatches($this->expressionLanguage, $instructions->getTemplateVariables())) {
                return $option->getValue();
            }
        }

        return null;
    }
}
