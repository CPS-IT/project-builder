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

namespace CPSIT\ProjectBuilder\Event;

use CPSIT\ProjectBuilder\Builder;

/**
 * BuildStepProcessedEvent.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class BuildStepProcessedEvent
{
    public function __construct(
        private Builder\Generator\Step\StepInterface $step,
        private Builder\BuildResult $buildResult,
        private bool $successful,
    ) {
    }

    public function getStep(): Builder\Generator\Step\StepInterface
    {
        return $this->step;
    }

    public function getBuildResult(): Builder\BuildResult
    {
        return $this->buildResult;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }
}
