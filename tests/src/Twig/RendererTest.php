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

namespace CPSIT\ProjectBuilder\Tests\Twig;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;

use function dirname;
use function json_decode;
use function trim;

/**
 * RendererTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class RendererTest extends Tests\ContainerAwareTestCase
{
    private Src\Twig\Renderer $subject;
    private Src\Builder\BuildInstructions $instructions;
    private Tests\Fixtures\DummyTemplateRenderingEventListener $eventListener;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Twig\Renderer::class)
            ->withRootPath(dirname(__DIR__, 2).'/templates')
        ;
        $this->instructions = new Src\Builder\BuildInstructions(
            self::$container->get('app.config'),
            'foo',
        );
        $this->eventListener = self::$container->get(Tests\Fixtures\DummyTemplateRenderingEventListener::class);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfNoTemplateIsDefined(): void
    {
        $this->expectException(Src\Exception\TemplateRenderingException::class);
        $this->expectExceptionCode(1654701586);
        $this->expectExceptionMessage('No template given. Please provide a valid template to be rendered.');

        $this->subject->render($this->instructions);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfTemplateIsMissing(): void
    {
        $this->expectException(Src\Exception\TemplateRenderingException::class);
        $this->expectExceptionCode(1653901911);
        $this->expectExceptionMessage('A template with identifier "foo" does not exist.');

        $this->subject->render($this->instructions, 'foo');
    }

    /**
     * @test
     */
    public function renderWithConfiguredFilesystemLoaderRendersTemplate(): void
    {
        self::assertFalse($this->eventListener->dispatched);

        $actual = $this->subject->render($this->instructions, 'dummy.twig', ['name' => 'World']);

        self::assertSame('Hello World!', trim($actual));
        self::assertTrue($this->eventListener->dispatched);
    }

    /**
     * @test
     */
    public function renderWithConfiguredArrayLoaderRendersTemplate(): void
    {
        $subject = $this->subject->withDefaultTemplate('Hello {{ name }}!');

        self::assertFalse($this->eventListener->dispatched);

        $actual = $subject->render($this->instructions, null, ['name' => 'World']);

        self::assertSame('Hello World!', trim($actual));
        self::assertTrue($this->eventListener->dispatched);
    }

    /**
     * @test
     */
    public function renderMergesBuildInstructionsAndAdditionalVariables(): void
    {
        $this->instructions->addTemplateVariable('foo', 'foo');
        $this->instructions->addTemplateVariable('bar', 'foo');

        $actual = $this->subject->render($this->instructions, 'dump.json.twig', ['bar' => 'bar']);
        $expected = [
            'instructions' => [
                'sourceDirectory' => dirname(__DIR__).'/templates/src',
                'sharedSourceDirectory' => dirname(__DIR__).'/templates/shared',
            ],
            'foo' => 'foo',
            'bar' => 'bar',
        ];

        self::assertJson($actual);
        self::assertSame($expected, json_decode($actual, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @test
     */
    public function renderRespectsVariablesModifiedThroughDispatchedEvent(): void
    {
        $subject = $this->subject->withDefaultTemplate('Hello {{ name }}!');

        $this->eventListener->variables = [
            'name' => 'bar',
        ];

        self::assertFalse($this->eventListener->dispatched);

        $actual = $subject->render($this->instructions, null, ['name' => 'foo']);

        self::assertSame('Hello bar!', trim($actual));
        self::assertTrue($this->eventListener->dispatched);
    }

    /**
     * @test
     */
    public function canRenderReturnsChecksWhetherGivenTemplateCanBeRendered(): void
    {
        self::assertTrue($this->subject->canRender('dummy.twig'));
        self::assertFalse($this->subject->canRender('foo'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->eventListener->dispatched = false;
        $this->eventListener->variables = [];
    }
}
