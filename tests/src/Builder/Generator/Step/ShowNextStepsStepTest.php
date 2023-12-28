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

namespace CPSIT\ProjectBuilder\Tests\Builder\Generator\Step;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

use function dirname;

/**
 * ShowNextStepsStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ShowNextStepsStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\ShowNextStepsStep $subject;
    private Src\Builder\BuildResult $result;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\ShowNextStepsStep::class);
        $this->result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(self::$config, 'foo'),
        );
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfNoTemplateFileIsGiven(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1652952150);
        $this->expectExceptionMessage('The config for "options.templateFile" does not exist or is not valid.');

        $this->subject->run($this->result);
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfTemplateFileDoesNotExist(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\ShowNextStepsStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions([], 'foo'),
            ),
        );

        $this->expectException(Src\Exception\IOException::class);
        $this->expectExceptionCode(1653394006);
        $this->expectExceptionMessageMatches('#^The file "[^"]+\\/foo" does not exist\\.$#');

        $this->subject->run($this->result);
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfTemplateFileCannotBeRendered(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\ShowNextStepsStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    [],
                    dirname(__DIR__, 3) . '/Fixtures/Files/invalid-template.twig',
                ),
            ),
        );

        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1652952150);
        $this->expectExceptionMessage('The config for "options.templateFile" does not exist or is not valid.');

        $this->subject->run($this->result);
    }

    #[Framework\Attributes\Test]
    public function runShowsNextStepsFromRenderedTemplateFileAndAppliesStep(): void
    {
        $this->subject->setConfig(
            new Src\Builder\Config\ValueObject\Step(
                Src\Builder\Generator\Step\ShowNextStepsStep::getType(),
                new Src\Builder\Config\ValueObject\StepOptions(
                    [],
                    dirname(__DIR__, 3) . '/Fixtures/Templates/yaml-template/templates/next-steps.html.twig',
                ),
            ),
        );

        $actual = $this->subject->run($this->result);
        $output = self::$io->getOutput();

        self::assertTrue($actual);
        self::assertStringContainsString('Next steps', $output);
        self::assertStringContainsString('Hello' . PHP_EOL . 'World', $output);
        self::assertTrue($this->result->isStepApplied($this->subject));
    }
}
