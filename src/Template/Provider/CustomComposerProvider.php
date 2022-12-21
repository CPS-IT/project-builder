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

namespace CPSIT\ProjectBuilder\Template\Provider;

use Composer\Factory;
use Composer\IO as ComposerIO;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

/**
 * CustomProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class CustomComposerProvider extends BaseComposerProvider implements CustomProviderInterface
{
    private ?string $url = null;

    public function __construct(
        IO\Messenger $messenger,
        Filesystem\Filesystem $filesystem,
    ) {
        parent::__construct($messenger, $filesystem);

        $this->io = new ComposerIO\ConsoleIO(
            new Console\Input\StringInput(''),
            Factory::createOutput(),
            new Console\Helper\HelperSet([
                new Console\Helper\QuestionHelper(),
            ]),
        );
    }

    public static function getName(): string
    {
        return 'Custom Composer registry';
    }

    public function requestCustomOptions(IO\Messenger $messenger): void
    {
        $inputReader = $messenger->createInputReader();

        $this->url = $inputReader->staticValue(
            'Base URL',
            null,
            true,
            new IO\Validator\ChainedValidator([
                new IO\Validator\NotEmptyValidator(),
                new IO\Validator\UrlValidator(),
            ]),
        );
    }

    public function getUrl(): string
    {
        if (null === $this->url) {
            throw Exception\InvalidResourceException::create('url');
        }

        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
