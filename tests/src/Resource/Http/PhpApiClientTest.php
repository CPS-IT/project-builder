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

namespace CPSIT\ProjectBuilder\Tests\Resource\Http;

use CPSIT\ProjectBuilder as Src;
use CPSIT\ProjectBuilder\Tests;

/**
 * PhpApiClientTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PhpApiClientTest extends Tests\ContainerAwareTestCase
{
    private Src\Resource\Http\PhpApiClient $subject;

    protected function setUp(): void
    {
        $this->subject = self::$container->get(Src\Resource\Http\PhpApiClient::class);
    }

    /**
     * @test
     */
    public function getLatestStableVersionThrowsExceptionOnInvalidResponse(): void
    {
        self::$mockHandler->append(self::createErroneousResponse());

        $this->expectException(Src\Exception\HttpException::class);
        $this->expectExceptionCode(1652861804);
        $this->expectExceptionMessage('Error from request to "https://www.php.net/releases/?json&version=8.0" (500): Something went wrong.');

        $this->subject->getLatestStableVersion('8.0');
    }

    /**
     * @test
     */
    public function getLatestStableVersionReturnsLatestStableVersionOfGivenBranch(): void
    {
        self::$mockHandler->append(self::createJsonResponse(['version' => '8.0.10']));

        self::assertSame('8.0.10', $this->subject->getLatestStableVersion('8.0'));
    }
}
