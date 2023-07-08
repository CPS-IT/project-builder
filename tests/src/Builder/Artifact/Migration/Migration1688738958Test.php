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

namespace CPSIT\ProjectBuilder\Tests\Builder\Artifact\Migration;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use PHPUnit\Framework;

/**
 * Migration1688738958Test.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Migration1688738958Test extends Tests\ContainerAwareTestCase
{
    private Src\Builder\Artifact\Migration\Migration1688738958 $subject;

    /**
     * @var array<string, mixed>
     */
    private array $artifact;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Builder\Artifact\Migration\Migration1688738958::class);
        $this->artifact = [
            'template' => [
                'provider' => [
                    'name' => Src\Template\Provider\PackagistProvider::getName(),
                ],
            ],
        ];
    }

    #[Framework\Attributes\Test]
    public function migrateMigratesTemplateProviderNameToTemplateProviderType(): void
    {
        $expected = [
            'template' => [
                'provider' => [
                    'type' => Src\Template\Provider\PackagistProvider::getType(),
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->migrate($this->artifact));
    }

    #[Framework\Attributes\Test]
    public function migrateThrowsExceptionIfNoMatchingProviderExistsForConfiguredTemplateName(): void
    {
        $this->expectExceptionObject(
            Src\Exception\UnknownTemplateProviderException::create('foo'),
        );

        $this->subject->migrate([
            'template' => [
                'provider' => [
                    'name' => 'foo',
                ],
            ],
        ]);
    }
}
