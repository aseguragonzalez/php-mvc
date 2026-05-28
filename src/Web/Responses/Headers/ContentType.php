<?php

declare(strict_types=1);

namespace PhpMvc\Responses\Headers;

final readonly class ContentType extends Header
{
    private function __construct(string $value)
    {
        parent::__construct('Content-Type', $value);
    }

    public static function json(): Header
    {
        return new self('application/json');
    }

    public static function xml(): Header
    {
        return new self('application/xml');
    }

    public static function html(): Header
    {
        return new self('text/html');
    }

    public static function text(): Header
    {
        return new self('text/plain');
    }

    public static function css(): Header
    {
        return new self('text/css');
    }

    public static function javascript(): Header
    {
        return new self('application/javascript');
    }

    public static function formUrlEncoded(): Header
    {
        return new self('application/x-www-form-urlencoded');
    }

    public static function formData(): Header
    {
        return new self('multipart/form-data');
    }

    public static function octetStream(): Header
    {
        return new self('application/octet-stream');
    }

    public static function pdf(): Header
    {
        return new self('application/pdf');
    }

    public static function zip(): Header
    {
        return new self('application/zip');
    }

    public static function tar(): Header
    {
        return new self('application/x-tar');
    }

    public static function gzip(): Header
    {
        return new self('application/gzip');
    }

    public static function rar(): Header
    {
        return new self('application/x-rar-compressed');
    }

    public static function sevenZip(): Header
    {
        return new self('application/x-7z-compressed');
    }

    public static function png(): Header
    {
        return new self('image/png');
    }

    public static function jpeg(): Header
    {
        return new self('image/jpeg');
    }

    public static function gif(): Header
    {
        return new self('image/gif');
    }

    public static function bmp(): Header
    {
        return new self('image/bmp');
    }

    public static function webp(): Header
    {
        return new self('image/webp');
    }

    public static function svg(): Header
    {
        return new self('image/svg+xml');
    }

    public static function mpeg(): Header
    {
        return new self('audio/mpeg');
    }

    public static function audioOgg(): Header
    {
        return new self('audio/ogg');
    }

    public static function wav(): Header
    {
        return new self('audio/wav');
    }

    public static function mp4(): Header
    {
        return new self('video/mp4');
    }

    public static function videoOgg(): Header
    {
        return new self('video/ogg');
    }

    public static function webm(): Header
    {
        return new self('video/webm');
    }
}
