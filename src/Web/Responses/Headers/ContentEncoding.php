<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Responses\Headers;

final readonly class ContentEncoding extends Header
{
    private function __construct(string $value)
    {
        parent::__construct('Content-Encoding', $value);
    }

    public static function gzip(): Header
    {
        return new self('gzip');
    }

    public static function deflate(): Header
    {
        return new self('deflate');
    }

    public static function br(): Header
    {
        return new self('br');
    }

    public static function identity(): Header
    {
        return new self('identity');
    }

    public static function compress(): Header
    {
        return new self('x-compress');
    }

    public static function xGzip(): Header
    {
        return new self('x-gzip');
    }
}
