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

namespace CPSIT\ProjectBuilder\Exception;

use CPSIT\ProjectBuilder\Template;

/**
 * InvalidTemplateSourceException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidTemplateSourceException extends Exception
{
    public static function forProvider(Template\Provider\ProviderInterface $provider): self
    {
        return new self(
            sprintf('The provider "%s" does not provide any template sources.', self::decorateProvider($provider)),
            1664557140
        );
    }

    public static function forFailedInstallation(Template\TemplateSource $templateSource): self
    {
        return new self(
            sprintf(
                'Installation of template source "%s" from provider "%s" failed.',
                $templateSource->getPackage()->getName(),
                self::decorateProvider($templateSource->getProvider())
            ),
            1664557307
        );
    }

    private static function decorateProvider(Template\Provider\ProviderInterface $provider): string
    {
        if ($provider instanceof Template\Provider\CustomProviderInterface) {
            return $provider->getUrl();
        }

        return $provider->getName();
    }
}
