<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\ProjectBuilder\Tests\Fixtures;

use CPSIT\ProjectBuilder\Builder;

/**
 * DummyStoppableStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyStoppableStep extends Builder\Generator\Step\AbstractStep implements Builder\Generator\Step\ProcessingStepInterface, Builder\Generator\Step\StoppableStepInterface
{
    use Builder\Generator\Step\ProcessingFilesTrait;

    public bool $stopped = false;

    public function run(Builder\BuildResult $buildResult): bool
    {
        return !$this->stopped;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public static function getType(): string
    {
        return 'dummyStoppable';
    }

    public static function supports(string $type): bool
    {
        return self::getType() === $type;
    }
}
