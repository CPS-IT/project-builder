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

namespace CPSIT\ProjectBuilder\Resource\Http;

use CPSIT\ProjectBuilder\Exception;
use CPSIT\ProjectBuilder\Helper;
use GuzzleHttp\Utils;
use Psr\Http\Client;
use Psr\Http\Message;
use stdClass;

use function property_exists;

/**
 * PhpApiClient.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PhpApiClient
{
    public function __construct(
        private readonly Client\ClientInterface $client,
        private readonly Message\RequestFactoryInterface $requestFactory,
    ) {}

    /**
     * @throws Client\ClientExceptionInterface
     * @throws Exception\HttpException
     */
    public function getLatestStableVersion(string $branch): string
    {
        $requestUrl = Helper\StringHelper::interpolate(
            'https://www.php.net/releases/?json&version={branch}',
            ['branch' => $branch],
        );
        $request = $this->requestFactory->createRequest('GET', $requestUrl);
        $response = $this->client->sendRequest($request);

        if (200 !== $response->getStatusCode()) {
            throw Exception\HttpException::forInvalidResponse($request, $response);
        }

        $json = Utils::jsonDecode((string) $response->getBody());

        // Fall back to .0 release if version cannot be determined via API
        if (!($json instanceof stdClass) || !property_exists($json, 'version')) {
            return $branch.'.0';
        }

        return $json->version;
    }
}
