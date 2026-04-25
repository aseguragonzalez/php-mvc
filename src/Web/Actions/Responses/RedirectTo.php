<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Actions\Responses;

use AlfonsoSG\Mvc\Responses\Headers\ContentType;
use AlfonsoSG\Mvc\Responses\Headers\Header;
use AlfonsoSG\Mvc\Responses\Headers\Location;
use AlfonsoSG\Mvc\Responses\StatusCode;

final class RedirectTo extends ActionResponse
{
    /**
     * @param array<Header> $headers
     */
    private function __construct(public readonly string $url, array $headers = [])
    {
        parent::__construct(data: new \stdClass(), headers: $headers, statusCode: StatusCode::Found);
    }

    /**
     * @param string                    $url     The URL to redirect to ({scheme}//{host}/{path})
     * @param null|array<string, mixed> $args
     * @param array<Header>             $headers
     */
    public static function create(string $url, ?array $args = [], array $headers = []): self
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }
        $urlWithQueryParams = $url;
        if (!empty($args)) {
            $queryString = http_build_query($args);
            if ('' !== $queryString) {
                $separator = null === parse_url($url, PHP_URL_QUERY) ? '?' : '&';
                $urlWithQueryParams .= $separator.$queryString;
            }
        }
        $newHeaders = [Location::toUrl($urlWithQueryParams)];
        if (empty(array_filter($headers, fn (Header $header) => true === $header instanceof ContentType))) {
            $newHeaders[] = ContentType::html();
        }

        return new self($urlWithQueryParams, array_merge($headers, $newHeaders));
    }
}
