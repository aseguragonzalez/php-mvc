<?php

declare(strict_types=1);

namespace Tests\Support\Psr7;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

final class TestPsr17Factory implements ResponseFactoryInterface, ServerRequestFactoryInterface, UriFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new TestResponse($code, $reasonPhrase);
    }

    /** @param array<string, mixed> $serverParams */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new TestServerRequest($method, $uri, $serverParams);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return new TestUri($uri);
    }
}
