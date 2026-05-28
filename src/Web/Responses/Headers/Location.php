<?php

declare(strict_types=1);

namespace PhpMvc\Responses\Headers;

final readonly class Location extends Header
{
    private function __construct(string $value)
    {
        parent::__construct('Location', $value);
    }

    public static function toUrl(string $url): Location
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (
            !filter_var($url, FILTER_VALIDATE_URL)
            || !is_string($scheme)
            || !in_array($scheme, ['http', 'https'])
        ) {
            throw new \InvalidArgumentException('Invalid URL provided for Location header');
        }

        return new self($url);
    }

    public static function toInternalUrl(string $url): Location
    {
        return new self($url);
    }
}
