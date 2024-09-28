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

namespace CPSIT\ProjectBuilder\Tests;

use Composer\Package;
use CPSIT\ProjectBuilder as Src;
use GuzzleHttp\Handler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Utils;
use Nyholm\Psr7;
use PHPUnit\Framework;
use Psr\Http\Client;
use Psr\Http\Message;
use Symfony\Component\DependencyInjection;

/**
 * ContainerAwareTestCase.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class ContainerAwareTestCase extends Framework\TestCase
{
    protected DependencyInjection\ContainerInterface $container;
    protected ClearableBufferIO $io;
    protected Handler\MockHandler $mockHandler;
    protected Src\Builder\Config\Config $config;

    protected function setUp(): void
    {
        $this->io = $this->createIO();
        $this->config = $this->createConfig();

        $this->container = Src\DependencyInjection\ContainerFactory::createForTesting()->get();
        $this->container->set('app.messenger', Src\IO\Messenger::create($this->io));
        $this->container->set('app.config', $this->config);
        $this->container->set(Client\ClientInterface::class, $this->createClient());
    }

    protected function createConfig(): Src\Builder\Config\Config
    {
        $config = new Src\Builder\Config\Config(
            'test',
            'Test',
            [
                new Src\Builder\Config\ValueObject\Step('dummy'),
            ],
            [
                new Src\Builder\Config\ValueObject\Property(
                    'foo',
                    'Foo',
                    null,
                    null,
                    null,
                    [
                        new Src\Builder\Config\ValueObject\SubProperty(
                            'bar',
                            'Bar',
                            'staticValue',
                        ),
                    ],
                ),
            ],
        );
        $config->setDeclaringFile(__FILE__);
        $config->setTemplateSource(
            new Src\Template\TemplateSource(
                new Fixtures\DummyProvider(),
                new Package\Package('foo/baz', '1.0.0', '1.0.0'),
            ),
        );

        return $config;
    }

    protected function createIO(): ClearableBufferIO
    {
        return new ClearableBufferIO();
    }

    protected function createClient(): Client\ClientInterface
    {
        $this->mockHandler = new Handler\MockHandler();

        $handler = new HandlerStack($this->mockHandler);

        return new \GuzzleHttp\Client(['handler' => $handler]);
    }

    /**
     * @param array<string, mixed> $json
     */
    protected static function createJsonResponse(array $json): Message\ResponseInterface
    {
        return new Psr7\Response(
            200,
            ['Content-Type' => 'application/json'],
            Utils::jsonEncode($json),
        );
    }

    protected static function createErroneousResponse(int $statusCode = 500): Message\ResponseInterface
    {
        return new Psr7\Response($statusCode, [], 'Something went wrong.');
    }
}
