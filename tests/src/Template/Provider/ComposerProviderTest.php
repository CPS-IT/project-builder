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

namespace CPSIT\ProjectBuilder\Tests\Template\Provider;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;
use donatj\MockWebServer;
use PHPUnit\Framework;
use ReflectionObject;
use ReflectionProperty;
use Symfony\Component\Filesystem;

/**
 * ComposerProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ComposerProviderTest extends Tests\ContainerAwareTestCase
{
    private Src\Template\Provider\ComposerProvider $subject;
    private MockWebServer\MockWebServer $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Src\Template\Provider\ComposerProvider(
            $this->container->get('app.messenger'),
            $this->container->get(Filesystem\Filesystem::class),
        );
        $this->server = new MockWebServer\MockWebServer();
        $this->server->start();

        $this->acceptInsecureConnections();
    }

    #[Framework\Attributes\Test]
    public function requestCustomOptionsAsksAndAppliesBaseUrl(): void
    {
        $this->overwriteIO();

        $this->io->setUserInputs(['https://example.com']);

        $this->subject->requestCustomOptions($this->container->get('app.messenger'));

        self::assertSame('https://example.com', $this->subject->getUrl());
    }

    #[Framework\Attributes\Test]
    public function getUrlThrowsExceptionIfNoUrlIsConfigured(): void
    {
        $this->expectExceptionObject(Src\Exception\InvalidResourceException::create('url'));

        $this->subject->getUrl();
    }

    #[Framework\Attributes\Test]
    public function setUrlAppliesGivenUrl(): void
    {
        $this->subject->setUrl('https://example.org');

        self::assertSame('https://example.org', $this->subject->getUrl());
    }

    #[Framework\Attributes\Test]
    public function listTemplateSourcesAddsAdditionalEmptyLineOnWrittenOutput(): void
    {
        $serverUrl = sprintf('http://%s:%s', $this->server->getHost(), $this->server->getPort());
        $io = $this->fetchIOViaReflection();

        $this->subject->setUrl($serverUrl);

        $io->silence();
        $io->write('foo');

        $this->subject->listTemplateSources();

        self::assertTrue($io->isOutputWritten());
        self::assertSame(LF, $this->io->getOutput());
    }

    #[Framework\Attributes\Test]
    public function listTemplateSourcesConnectsToComposerHostToFetchAvailablePackages(): void
    {
        $this->overwriteIO();

        $serverUrl = sprintf('http://%s:%s', $this->server->getHost(), $this->server->getPort());

        $this->subject->setUrl($serverUrl);

        $this->subject->listTemplateSources();

        $lastRequest = $this->server->getLastRequest();

        self::assertNotNull($lastRequest);
        self::assertSame('/packages.json', $lastRequest->getRequestUri());
    }

    private function overwriteIO(): void
    {
        $this->setPropertyValueOnObject($this->subject, 'io', $this->io);
    }

    private function acceptInsecureConnections(): void
    {
        $this->setPropertyValueOnObject($this->subject, 'acceptInsecureConnections', true);
    }

    private function fetchIOViaReflection(): Src\IO\Console\TraceableConsoleIO
    {
        $reflectionProperty = $this->getReflectionProperty($this->subject, 'io');
        $io = $reflectionProperty->getValue($this->subject);

        self::assertInstanceOf(Src\IO\Console\TraceableConsoleIO::class, $io);

        return $io;
    }

    private function setPropertyValueOnObject(object $object, string $propertyName, mixed $value): void
    {
        $reflectionProperty = $this->getReflectionProperty($object, $propertyName);
        $reflectionProperty->setValue($object, $value);
    }

    private function getReflectionProperty(object $object, string $propertyName): ReflectionProperty
    {
        $reflectionObject = new ReflectionObject($object);

        return $reflectionObject->getProperty($propertyName);
    }

    protected function tearDown(): void
    {
        $this->server->stop();
    }
}
