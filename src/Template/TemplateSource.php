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

namespace CPSIT\ProjectBuilder\Template;

use Composer\Package;

/**
 * TemplateSource.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TemplateSource
{
    private bool $dynamicVersionConstraint = false;

    public function __construct(
        private readonly Provider\ProviderInterface $provider,
        private Package\PackageInterface $package,
    ) {}

    public function getProvider(): Provider\ProviderInterface
    {
        return $this->provider;
    }

    public function getPackage(): Package\PackageInterface
    {
        return $this->package;
    }

    public function setPackage(Package\PackageInterface $package): self
    {
        $this->package = $package;

        return $this;
    }

    public function shouldUseDynamicVersionConstraint(): bool
    {
        return $this->dynamicVersionConstraint;
    }

    public function useDynamicVersionConstraint(bool $dynamicVersionConstraint = true): self
    {
        $this->dynamicVersionConstraint = $dynamicVersionConstraint;

        return $this;
    }
}
