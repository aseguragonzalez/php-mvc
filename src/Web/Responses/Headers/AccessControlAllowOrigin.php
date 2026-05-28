<?php

declare(strict_types=1);

namespace PhpMvc\Responses\Headers;

final readonly class AccessControlAllowOrigin extends Header
{
    private function __construct(string $value)
    {
        parent::__construct('Access-Control-Allow-Origin', $value);
    }

    public static function any(): Header
    {
        return new self('*');
    }

    public static function none(): Header
    {
        return new self('null');
    }

    public static function specific(string $origin): Header
    {
        return new self($origin);
    }
}
