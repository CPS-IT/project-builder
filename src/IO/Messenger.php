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

namespace CPSIT\ProjectBuilder\IO;

use Composer\IO;
use Composer\Package;
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Resource;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console;

use function array_map;
use function count;
use function implode;
use function is_scalar;
use function is_string;
use function sprintf;
use function trim;

/**
 * Messenger.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Messenger
{
    private static ?string $lastProgressOutput = null;

    private function __construct(
        private readonly IO\IOInterface $io,
        private readonly Console\Terminal $terminal,
    ) {}

    public static function create(IO\IOInterface $io): self
    {
        return new self($io, new Console\Terminal());
    }

    /**
     * Factory method for {@see InputReader}.
     *
     * This factory method is used in the service container
     * to provide an instance of {@see InputReader}.
     */
    public function createInputReader(): InputReader
    {
        return new InputReader($this->io);
    }

    public function clearScreen(): void
    {
        $this->write(sprintf("\033\143"));
    }

    public function clearLine(): void
    {
        $this->write("\x1b[1A", false);
    }

    public function welcome(): void
    {
        $this->getIO()->write([
            '<comment>'.Emoji::Sparkles->value.' Welcome to the Project Builder!</comment>',
            '<comment>==================================</comment>',
            '',
            'The <comment>Project Builder</comment> helps you create Composer based projects with templates.',
            'A template holds project/framework related information such as Composer dependencies and configuration.',
        ]);
        $this->newLine();
        $this->comment('You may find templates on public and private providers/registries such as Satis, GitLab or GitHub.');
        $this->comment('Let\'s start by looking for templates on Packagist.org:');
        $this->newLine();
    }

    public function section(string $name): void
    {
        $length = mb_strlen($name);

        $this->newLine();
        $this->getIO()->write([
            sprintf('<comment>%s</comment>', $name),
            sprintf('<comment>%s</comment>', str_repeat('-', $length)),
        ]);
        $this->newLine();
    }

    public function comment(string $comment): void
    {
        $this->write('<fg=gray>'.$comment.'</>');
    }

    /**
     * @param non-empty-list<Template\Provider\ProviderInterface> $providers
     */
    public function selectProvider(array $providers): Template\Provider\ProviderInterface
    {
        $labels = array_map(
            fn (Template\Provider\ProviderInterface $provider) => $provider::getName(),
            $providers,
        );
        $defaultIdentifier = array_key_first($providers);

        $index = $this->getIO()->select(
            self::decorateLabel('Where can we find the project template?', $defaultIdentifier),
            $labels,
            (string) $defaultIdentifier,
        );

        $selectedProvider = $providers[(int) $index];

        if (!$selectedProvider instanceof Template\Provider\CustomProviderInterface) {
            $this->newLine();

            return $selectedProvider;
        }

        $selectedProvider->requestCustomOptions($this);

        $this->newLine();

        return $selectedProvider;
    }

    /**
     * @throws Exception\InvalidTemplateSourceException
     */
    public function selectTemplateSource(Template\Provider\ProviderInterface $provider): ?Template\TemplateSource
    {
        $this->progress(
            sprintf('Fetching templates from <href=%s>%s</> ...', $provider->getUrl(), $provider->getUrl()),
            IO\IOInterface::NORMAL,
        );

        $templateSources = $provider->listTemplateSources();

        $this->done();
        $this->newLine();

        if ([] === $templateSources) {
            throw Exception\InvalidTemplateSourceException::forProvider($provider);
        }

        $labels = array_map($this->decorateTemplateSource(...), $templateSources);
        $labels[] = '<comment>Try a different provider (e.g. Satis or GitHub)</comment>';

        $defaultIdentifier = array_key_first($templateSources);

        $index = $this->getIO()->select(
            self::decorateLabel('Select a project template or try a different provider', $defaultIdentifier),
            $labels,
            (string) $defaultIdentifier,
        );

        // Early return if another provider should be used
        if (array_key_last($labels) === (int) $index) {
            return null;
        }

        $this->newLine();

        return $templateSources[(int) $index];
    }

    public function confirmTemplateSourceRetry(\Exception $exception): bool
    {
        $this->getIO()->write([
            '<error>'.$exception->getMessage().'</error>',
            'You can go one step back and select another template provider.',
            sprintf(
                'For more information, take a look at the <href=%s>documentation</>.',
                'https://project-builder.cps-it.de/development/configuration.html',
            ),
            '',
        ]);

        $label = self::decorateLabel('Continue?', 'Y', true, ['n']);

        return $this->getIO()->askConfirmation($label);
    }

    public function confirmOverwrite(string $directory): bool
    {
        $this->getIO()->write([
            'All project files are temporarily generated.',
            sprintf('To complete the project creation, they are now moved to "%s".', $directory),
            '<comment>Note: This removes all existing files in this directory!</comment>',
        ]);

        $label = self::decorateLabel('Continue?', 'Y', true, ['n']);

        return $this->getIO()->askConfirmation($label);
    }

    public function confirmProjectRegeneration(): bool
    {
        $this->getIO()->write('If you want, you can restart project generation now.');

        $label = self::decorateLabel('Restart?', 'Y', true, ['n']);

        return $this->getIO()->askConfirmation($label);
    }

    public function confirmRunCommand(string $command): bool
    {
        $this->getIO()->write([
            sprintf(
                'Preparing to run "%s" in the project dir.',
                $command,
            ),
        ]);

        $label = self::decorateLabel('Do you wish to run this command?', 'Y', true, ['n']);

        return $this->getIO()->askConfirmation($label);
    }

    /**
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    public function progress(string $message, int $verbosity = IO\IOInterface::VERBOSE, bool $overwrite = false): void
    {
        if (!$this->checkVerbosity($verbosity)) {
            return;
        }

        $message = sprintf('<comment>%s</comment> ', rtrim($message));
        $this->writeWithEmoji(Emoji::HourglassFlowingSand->value, $message, $overwrite);

        self::$lastProgressOutput = $message;
    }

    public function done(): void
    {
        if (null !== self::$lastProgressOutput) {
            $this->writeWithEmoji(
                Emoji::WhiteHeavyCheckMark->value,
                self::$lastProgressOutput.'<info>Done</info>',
                true,
            );
        }
    }

    public function failed(): void
    {
        if (null !== self::$lastProgressOutput) {
            $this->writeWithEmoji(
                Emoji::Prohibited->value,
                self::$lastProgressOutput.'<error>Failed</error>',
                true,
            );
        }
    }

    public function decorateResult(Builder\BuildResult $result): void
    {
        $this->getIO()->write([
            '<info>Build result</info>',
            '<info>============</info>',
        ]);
        $this->newLine();

        $resultMessages = [
            'Exit status' => $result->isMirrored()
                ? Emoji::WhiteHeavyCheckMark->value.' Completed'
                : Emoji::Prohibited->value.' <comment>Aborted</comment>',
            'Project type' => $result->getInstructions()->getConfig()->getName(),
        ];

        $processedFiles = $result->getProcessedFiles($result->getWrittenDirectory());
        $hasProcessedFiles = $result->isMirrored() && [] !== $processedFiles;

        if ($hasProcessedFiles) {
            $resultMessages['Written directory'] = $result->getWrittenDirectory();
            $resultMessages['Written files'] = $this->decorateNumberOfProcessedFiles($processedFiles);
        }

        foreach ($resultMessages as $label => $value) {
            $this->write(sprintf('%s: <info>%s</info>', $label, $value));
        }

        if ($hasProcessedFiles && $this->isVerbose()) {
            foreach ($processedFiles as $processedFile) {
                $this->write(sprintf('  * <comment>%s</comment>', $processedFile->getTargetFile()->getRelativePathname()));
            }
        }

        $this->newLine();

        if ($result->isMirrored()) {
            $this->writeWithEmoji(
                Emoji::PartyPopper->value,
                '<info>Congratulations, your new project was successfully built!</info>',
            );
        } else {
            $this->writeWithEmoji(
                Emoji::WoozyFace->value,
                '<comment>Project generation was aborted. Please try again.</comment>',
            );
        }

        $this->newLine();
    }

    /**
     * @param list<Resource\Local\ProcessedFile> $processedFiles
     */
    private function decorateNumberOfProcessedFiles(array $processedFiles): string
    {
        if ([] === $processedFiles || $this->isVerbose()) {
            return '';
        }

        $mentionedFiles = [];
        $length = 0;
        $maxLength = $this->terminal->getWidth() - 40;
        $remaining = count($processedFiles);

        foreach ($processedFiles as $processedFile) {
            $relativePathname = $processedFile->getTargetFile()->getRelativePathname();
            $currentLength = Console\Helper\Helper::length($relativePathname);
            $calculatedLength = $length + $currentLength;

            if ($calculatedLength > $maxLength) {
                break;
            }

            $mentionedFiles[] = $relativePathname;
            $length = $calculatedLength;
            --$remaining;
        }

        if ([] === $mentionedFiles) {
            return sprintf('%s file%s', $remaining, 1 === $remaining ? '' : 's');
        }

        $result = implode(', ', $mentionedFiles);

        if (0 !== $remaining) {
            $result .= sprintf(' and %d more', $remaining);
        }

        return $result;
    }

    /**
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    public function newLine(int $verbosity = IO\IOInterface::NORMAL): void
    {
        $this->write('', true, $verbosity);
    }

    /**
     * @param string|list<string>            $messages
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    public function write(string|array $messages, bool $newLine = true, int $verbosity = IO\IOInterface::NORMAL): void
    {
        $this->getIO()->write($messages, $newLine, $verbosity);
    }

    public function writeWithEmoji(string $emoji, string $message, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->clearLine();
        }

        $this->write($emoji.' '.$message);
    }

    public function error(string $message): void
    {
        $this->writeWithEmoji(Emoji::RotatingLight->value, '<error>'.$message.'</error>');
    }

    public function isVerbose(): bool
    {
        return $this->io->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->io->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->io->isDebug();
    }

    public function isInteractive(): bool
    {
        return $this->io->isInteractive();
    }

    /**
     * @param list<string> $alternatives
     */
    public static function decorateLabel(
        string $label,
        mixed $default = null,
        bool $required = true,
        array $alternatives = [],
        bool $multiple = false,
    ): string {
        $label = preg_replace('/(\s*:\s*)?$/', '', $label);

        $notices = [];

        if (!$required) {
            $notices[] = 'optional';
        }
        if ($multiple) {
            $notices[] = 'multiple allowed, separate by comma';
        }

        if ([] !== $notices) {
            $label .= sprintf(' (<comment>%s</comment>)', implode('</comment>, <comment>', $notices));
        }

        if ([] !== $alternatives) {
            array_unshift($alternatives, '');
        }
        if (is_scalar($default) && (!is_string($default) || '' !== trim($default))) {
            $label .= sprintf(' [<info>%s</info>%s]', (string) $default, implode('/', $alternatives));
        }

        $label .= ': ';

        return $label;
    }

    private function decorateTemplateSource(Template\TemplateSource $templateSource): string
    {
        $package = $templateSource->getPackage();
        $name = $package->getPrettyName();

        if (!$package instanceof Package\CompletePackageInterface) {
            return $name;
        }

        $description = $package->getDescription();

        $decoratedTemplateSource = (null === $description || '' === trim($description))
            ? $name
            : sprintf('%s <fg=gray>(%s)</>', $description, $name);

        if (!$package->isAbandoned()) {
            return $decoratedTemplateSource;
        }

        return sprintf(
            '%s <warning>Abandoned! %s</warning>',
            $decoratedTemplateSource,
            match ($package->getReplacementPackage()) {
                null => 'No replacement was suggested.',
                default => 'Use '.$package->getReplacementPackage().' instead.',
            },
        );
    }

    /**
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    private function checkVerbosity(int $verbosity): bool
    {
        return match ($verbosity) {
            IO\IOInterface::QUIET => false,
            IO\IOInterface::NORMAL => true,
            IO\IOInterface::VERBOSE => $this->io->isVerbose(),
            IO\IOInterface::VERY_VERBOSE => $this->io->isVeryVerbose(),
            IO\IOInterface::DEBUG => $this->io->isDebug(),
            default => false,
        };
    }

    private function getIO(): IO\IOInterface
    {
        self::$lastProgressOutput = null;

        return $this->io;
    }
}
