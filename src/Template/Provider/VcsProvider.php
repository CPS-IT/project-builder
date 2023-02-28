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

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;

/**
 * VcsProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsProvider extends BaseProvider implements CustomProviderInterface
{
    private const TYPE = 'vcs';

    private ?string $url = null;

    /**
     * @var list<array{type: string, url: string}>
     */
    private array $repositories = [];

    public function requestCustomOptions(IO\Messenger $messenger): void
    {
        $inputReader = $messenger->createInputReader();

        $this->url = $inputReader->staticValue('Repository URL', required: true);

        while ($inputReader->ask('Does the repository require additional transitive repositories?', default: false)) {
            $this->repositories[] = [
                'type' => $inputReader->staticValue('Type', 'vcs', true),
                'url' => $inputReader->staticValue('URL', required: true),
            ];

            $messenger->writeWithEmoji(IO\Emoji::WhiteHeavyCheckMark->value, 'Repository added.');
            $messenger->newLine();
        }
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

    protected function createComposerJson(array $templateSources, array $repositories = []): string
    {
        $repositories = [...$repositories, ...$this->repositories];

        return parent::createComposerJson($templateSources, $repositories);
    }

    protected function getRepositoryType(): string
    {
        return 'vcs';
    }

    public static function getName(): string
    {
        return 'VCS repository';
    }

    public static function getType(): string
    {
        return self::TYPE;
    }

    public static function supports(string $type): bool
    {
        return self::TYPE === $type;
    }
}
