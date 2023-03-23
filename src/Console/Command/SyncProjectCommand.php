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

namespace CPSIT\ProjectBuilder\Console\Command;

use Composer\Command;
use CPSIT\Migrator;
use CPSIT\ProjectBuilder\Builder;
use CPSIT\ProjectBuilder\DependencyInjection;
use CPSIT\ProjectBuilder\Helper;
use CPSIT\ProjectBuilder\IO;
use CPSIT\ProjectBuilder\Migration;
use CPSIT\ProjectBuilder\Paths;
use CPSIT\ProjectBuilder\Template;
use Symfony\Component\Console;
use Symfony\Component\Filesystem;

/**
 * SyncProjectCommand
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SyncProjectCommand extends Command\BaseCommand
{
    private const SUCCESSFUL = 0;
    private const FAILED = 1;

    public function __construct(
        private readonly IO\Messenger $messenger,
        private readonly Migration\ProjectMigrator $migrator,
    ) {
        parent::__construct('project:sync');
    }

    public static function create(IO\Messenger $messenger): self
    {
        $factory = DependencyInjection\ContainerFactory::create();
        $container = $factory->get();
        $container->set('app.messenger', $messenger);

        return new SyncProjectCommand(
            $messenger,
            $container->get(Migration\ProjectMigrator::class),
        );
    }

    protected function configure(): void
    {
        $this->setDescription('Sync an existing project with the latest version of its project template');

        $this->addArgument(
            'project-directory',
            Console\Input\InputArgument::OPTIONAL,
            'Absolute path to the project directory',
            Helper\FilesystemHelper::getWorkingDirectory(),
        );
        $this->addArgument(
            'artifact-path',
            Console\Input\InputArgument::OPTIONAL,
            'Relative path to the project build artifact',
            Paths::BUILD_ARTIFACT_DEFAULT_PATH,
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $projectDirectory = Helper\FilesystemHelper::resolveRelativePath($input->getArgument('project-directory'));
        $artifactPath = $input->getArgument('artifact-path');

        $this->migrator->migrate($projectDirectory, $artifactPath);

        return self::SUCCESSFUL;

//        $subCommand = new Migrator\Command\MigrateCommand($this->migrator);
//        $subInput = new Console\Input\ArrayInput([
//            'command' => $subCommand->getName(),
//            'base-directory' => $projectDirectory,
//            // @todo determine directories
//            'source-directory' => '',
//            'target-directory' => '',
//        ]);
//
//        $this->getApplication()->add($subCommand);
//
//        return $this->getApplication()->run($subInput, $output);
    }
}
