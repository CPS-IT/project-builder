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
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

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

    public function __construct(IO\Messenger $messenger, Filesystem\Filesystem $filesystem)
    {
        parent::__construct($messenger, $filesystem);

        $this->io = new IO\Console\TraceableConsoleIO(
            new Console\Input\StringInput(''),
            Factory::createOutput(),
            new Console\Helper\HelperSet([
                new Console\Helper\QuestionHelper(),
            ]),
        );
    }

    public function requestCustomOptions(IO\Messenger $messenger): void
    {
        $inputReader = $messenger->createInputReader();

        $this->url = $inputReader->staticValue(
            'Repository URL <fg=gray>(e.g. https://github.com/vendor/template.git)</>',
            required: true,
        );
    }

    public function listTemplateSources(): array
    {
        $templateSources = parent::listTemplateSources();

        if ($this->io instanceof IO\Console\TraceableConsoleIO && $this->io->isOutputWritten()) {
            $this->messenger->newLine();
        }

        return $templateSources;
    }

    public function installTemplateSource(Template\TemplateSource $templateSource): void
    {
        try {
            parent::installTemplateSource($templateSource);
        } catch (Exception\InvalidTemplateSourceException $exception) {
            // If additional repositories were already added, installation is obviously not possible
            if ([] !== $this->repositories) {
                throw $exception;
            }

            // Ask for additional repositories to resolve probable installation failures
            $this->messenger->newLine();
            $this->messenger->error(sprintf('Unable to install %s.', $templateSource->getPackage()->getName()));
            $this->askForAdditionalRepositories();

            // Fail with original exception if no additional repositories were added
            if ([] === $this->repositories) {
                throw $exception;
            }

            // Retry installation with additional repositories
            $this->installTemplateSource($templateSource);
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

    private function askForAdditionalRepositories(): void
    {
        $inputReader = $this->messenger->createInputReader();

        $this->messenger->newLine();
        $this->messenger->comment('Some VCS repositories require additional transitive packages.');
        $this->messenger->comment('If no additional packages are required, the selected package probably cannot be used.');
        $this->messenger->newLine();

        while ($inputReader->ask('Are additional transitive packages required?', default: false)) {
            $this->repositories[] = [
                'type' => $inputReader->staticValue('Package type', 'vcs', true),
                'url' => $inputReader->staticValue('Package URL', required: true),
            ];

            $this->messenger->writeWithEmoji(IO\Emoji::WhiteHeavyCheckMark->value, 'Package added.');
            $this->messenger->newLine();
        }
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
        return 'VCS repository (e.g. GitHub)';
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
