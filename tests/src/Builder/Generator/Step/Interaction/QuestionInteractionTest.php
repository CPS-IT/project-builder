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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step\Interaction;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * QuestionInteractionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class QuestionInteractionTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\Interaction\QuestionInteraction $subject;
    private Src\Builder\BuildInstructions $instructions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Src\Builder\Generator\Step\Interaction\QuestionInteraction::class);
        $this->instructions = new Src\Builder\BuildInstructions($this->config, 'foo');
    }

    #[Framework\Attributes\Test]
    public function interactUsesTruAndFalseAsDefaultYesNoValues(): void
    {
        $interactionSubject = $this->buildInteractionSubject();

        $this->io->setUserInputs(['yes', 'no']);

        self::assertTrue($this->subject->interact($interactionSubject, $this->instructions));
        self::assertFalse($this->subject->interact($interactionSubject, $this->instructions));
    }

    #[Framework\Attributes\Test]
    public function interactReturnsValueFromMatchingOption(): void
    {
        $interactionSubject = $this->buildInteractionSubject([
            new Src\Builder\Config\ValueObject\PropertyOption('foo', 'selected'),
            new Src\Builder\Config\ValueObject\PropertyOption('bar', 'not selected'),
        ]);

        $this->io->setUserInputs(['yes', 'no']);

        self::assertSame('foo', $this->subject->interact($interactionSubject, $this->instructions));
        self::assertSame('bar', $this->subject->interact($interactionSubject, $this->instructions));
    }

    #[Framework\Attributes\Test]
    public function interactUsesFallbackConditionIfOnlyOptionValueIsConfigured(): void
    {
        $interactionSubject = $this->buildInteractionSubject([
            new Src\Builder\Config\ValueObject\PropertyOption('foo'),
        ]);

        $this->io->setUserInputs(['yes', 'no']);

        self::assertSame('foo', $this->subject->interact($interactionSubject, $this->instructions));
        self::assertFalse($this->subject->interact($interactionSubject, $this->instructions));
    }

    /**
     * @param list<Src\Builder\Config\ValueObject\PropertyOption> $options
     */
    private function buildInteractionSubject(array $options = []): Src\Builder\Config\ValueObject\CustomizableInterface
    {
        return new Src\Builder\Config\ValueObject\SubProperty(
            'foo',
            'foo',
            'foo',
            null,
            null,
            null,
            $options,
            false,
        );
    }
}
