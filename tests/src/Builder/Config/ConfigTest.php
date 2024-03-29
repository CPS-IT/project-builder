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

namespace CPSIT\ProjectBuilder\Tests\Builder\Config;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use PHPUnit\Framework;

use function serialize;

/**
 * ConfigTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigTest extends Framework\TestCase
{
    private Src\Builder\Config\Config $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Builder\Config\Config(
            'identifier',
            'name',
            [
                new Src\Builder\Config\ValueObject\Step('type'),
            ],
            [
                new Src\Builder\Config\ValueObject\Property('identifier', 'name'),
            ],
        );
    }

    #[Framework\Attributes\Test]
    public function getIdentifierReturnsIdentifier(): void
    {
        self::assertSame('identifier', $this->subject->getIdentifier());
    }

    #[Framework\Attributes\Test]
    public function getNameReturnsName(): void
    {
        self::assertSame('name', $this->subject->getName());
    }

    #[Framework\Attributes\Test]
    public function getStepsReturnsSteps(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\Step('type'),
            ],
            $this->subject->getSteps(),
        );
    }

    #[Framework\Attributes\Test]
    public function getPropertiesReturnsProperties(): void
    {
        self::assertEquals(
            [
                new Src\Builder\Config\ValueObject\Property('identifier', 'name'),
            ],
            $this->subject->getProperties(),
        );
    }

    #[Framework\Attributes\Test]
    public function getDeclaringFileThrowsExceptionIfDeclaringFileIsNotSet(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1653424186);
        $this->expectExceptionMessage('The config file for "identifier" could not be determined.');

        $this->subject->getDeclaringFile();
    }

    #[Framework\Attributes\Test]
    public function setDeclaringFileAppliesDeclaringFile(): void
    {
        self::assertSame('foo', $this->subject->setDeclaringFile('foo')->getDeclaringFile());
    }

    #[Framework\Attributes\Test]
    public function getTemplateSourceThrowsExceptionIfTemplateSourceIsNotSet(): void
    {
        $this->expectException(Src\Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1673458682);
        $this->expectExceptionMessage('The template source for "identifier" could not be determined.');

        $this->subject->getTemplateSource();
    }

    #[Framework\Attributes\Test]
    public function setTemplateSourceAppliesTemplateSource(): void
    {
        $templateSource = new Src\Template\TemplateSource(
            new Src\Tests\Fixtures\DummyProvider(),
            new Package\Package('foo/baz', '1.0.0', '1.0.0'),
        );

        self::assertSame($templateSource, $this->subject->setTemplateSource($templateSource)->getTemplateSource());
    }

    #[Framework\Attributes\Test]
    public function buildHashCalculatesConfigHash(): void
    {
        $hash = sha1(
            serialize([
                'identifier' => $this->subject->getIdentifier(),
                'name' => $this->subject->getName(),
                'steps' => $this->subject->getSteps(),
                'properties' => $this->subject->getProperties(),
            ]),
        );

        self::assertSame($hash, $this->subject->buildHash());
    }
}
