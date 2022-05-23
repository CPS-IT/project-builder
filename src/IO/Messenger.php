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
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\Resource;
use Symfony\Component\Console;
use function count;
use function implode;
use function is_scalar;
use function is_string;
use function sprintf;
use function strlen;
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

    private IO\IOInterface $io;
    private Console\Terminal $terminal;

    private function __construct(IO\IOInterface $io, Console\Terminal $terminal)
    {
        $this->io = $io;
        $this->terminal = $terminal;
    }

    public static function create(IO\IOInterface $io): self
    {
        return new self($io, new Console\Terminal());
    }

    public function createInputReader(): InputReader
    {
        return new InputReader($this->io);
    }

    public function clearScreen(): void
    {
        $this->write(sprintf("\033\143"));
    }

    public function welcome(): void
    {
        $this->getIO()->write([
            '<comment>'.Emoji::SPARKLES.' Welcome to the <info>CPS Project Builder</info>!</comment>',
            '<comment>======================================</comment>',
        ]);
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
     * @param array<string, string> $templates
     */
    public function selectTemplate(array $templates): string
    {
        $identifiers = array_keys($templates);
        $labels = array_values($templates);
        $defaultIdentifier = array_key_first($identifiers);

        $index = $this->getIO()->select(
            self::decorateLabel('Please select a project you would like to create', $defaultIdentifier),
            $labels,
            (string) $defaultIdentifier
        );

        $this->newLine();

        return $identifiers[(int) $index];
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

    /**
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    public function progress(string $message, int $verbosity = IO\IOInterface::VERBOSE): void
    {
        if (!$this->checkVerbosity($verbosity)) {
            return;
        }

        $message = sprintf('<comment>%s</comment> ', rtrim($message));
        $this->writeWithEmoji(Emoji::HOURGLASS_FLOWING_SAND, $message);

        self::$lastProgressOutput = $message;
    }

    public function done(): void
    {
        if (null !== self::$lastProgressOutput) {
            $this->writeWithEmoji(
                Emoji::WHITE_HEAVY_CHECK_MARK,
                self::$lastProgressOutput.'<info>Done</info>',
                true
            );
        }
    }

    public function failed(): void
    {
        if (null !== self::$lastProgressOutput) {
            $this->writeWithEmoji(
                Emoji::PROHIBITED,
                self::$lastProgressOutput.'<error>Failed</error>',
                true
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
                ? Emoji::WHITE_HEAVY_CHECK_MARK.' Completed'
                : Emoji::PROHIBITED.' <comment>Aborted</comment>',
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
                Emoji::PARTY_POPPER,
                '<info>Congratulations, your new project was successfully built!</info>'
            );
        } else {
            $this->writeWithEmoji(
                Emoji::WOOZY_FACE,
                '<comment>Project generation was aborted. Please try again.</comment>'
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
            $currentLength = strlen($relativePathname);
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
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    public function write(string $message, bool $newLine = true, int $verbosity = IO\IOInterface::NORMAL): void
    {
        $this->getIO()->write($message, $newLine, $verbosity);
    }

    public function writeWithEmoji(string $emoji, string $message, bool $clear = false): void
    {
        if ($clear) {
            $this->write("\x1b[1A", false);
        }

        $this->write($emoji.' '.$message);
    }

    public function error(string $message): void
    {
        $this->writeWithEmoji(Emoji::ROTATING_LIGHT, '<error>'.$message.'</error>');
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
     * @param mixed        $default
     * @param list<string> $alternatives
     */
    public static function decorateLabel(
        string $label,
        $default = null,
        bool $required = true,
        array $alternatives = []
    ): string {
        $label = preg_replace('/(\s*:\s*)?$/', '', $label);

        if (!$required) {
            $label .= ' (<comment>optional</comment>)';
        }
        if ([] !== $alternatives) {
            array_unshift($alternatives, '');
        }
        if (is_scalar($default) && (!is_string($default) || '' !== trim($default))) {
            $label .= sprintf(' [<info>%s</info>%s]', $default, implode('/', $alternatives));
        }

        $label .= ': ';

        return $label;
    }

    /**
     * @param int-mask-of<IO\IOInterface::*> $verbosity
     */
    private function checkVerbosity(int $verbosity): bool
    {
        switch ($verbosity) {
            case IO\IOInterface::QUIET:
                return false;

            case IO\IOInterface::NORMAL:
                return true;

            case IO\IOInterface::VERBOSE:
                return $this->io->isVerbose();

            case IO\IOInterface::VERY_VERBOSE:
                return $this->io->isVeryVerbose();

            case IO\IOInterface::DEBUG:
                return $this->io->isDebug();
        }

        return false;
    }

    private function getIO(): IO\IOInterface
    {
        self::$lastProgressOutput = null;

        return $this->io;
    }
}
