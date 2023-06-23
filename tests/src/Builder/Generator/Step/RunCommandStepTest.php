<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Martin Adler <mteu@mailbox.org>
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

/**
 * RunCommandStepTest.
 *
 * @author Martin Adler <mteu@mailbox.org>
 * @license GPL-3.0-or-later
 */
class RunCommandStepTest extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Generator\Step\RunCommandStep $subject;
    private Src\Builder\BuildResult $result;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Generator\Step\RunCommandStep::class);
        $this->result = new Src\Builder\BuildResult(
            new Src\Builder\BuildInstructions(
                self::$config,
                'foo',
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function runThrowsExceptionIfNoCommandIsGiven(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1652952150);
        $this->expectExceptionMessage('The config for "options.command" does not exist or is not valid.');

        $this->subject->run($this->result);
    }
}
