<?php

declare(strict_types=1);

namespace PhpMvc;

use Psr\Http\Message\ResponseInterface;

final class ResponseEmitter
{
    private function __construct() {}

    public static function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
        echo $response->getBody();
    }
}
