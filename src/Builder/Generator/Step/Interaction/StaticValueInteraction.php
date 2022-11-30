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

use function is_string;

/**
 * StaticValueInteraction.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StaticValueInteraction implements InteractionInterface
{
    private const TYPE = 'staticValue';

    public function __construct(
        private IO\InputReader $reader,
        private Twig\Renderer $renderer,
        private IO\Validator\ValidatorFactory $validatorFactory,
    ) {
    }

    public function interact(
        Builder\Config\ValueObject\CustomizableInterface $subject,
        Builder\BuildInstructions $instructions,
    ): ?string {
        $validator = $this->validatorFactory->getAll($subject->getValidators());
        $defaultValue = $this->renderDefaultValue($subject->getDefaultValue(), $instructions);

        return $this->reader->staticValue(
            $subject->getName(),
            $defaultValue,
            $subject->isRequired(),
            $validator,
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

    private function renderDefaultValue(mixed $defaultValue, Builder\BuildInstructions $instructions): ?string
    {
        if (!is_string($defaultValue)) {
            return null;
        }

        $renderer = $this->renderer->withDefaultTemplate($defaultValue);

        return $renderer->render($instructions);
    }
}
