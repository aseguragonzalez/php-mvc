<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Routes;

enum RouteMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Delete = 'DELETE';
    case Patch = 'PATCH';
    case Options = 'OPTIONS';
    case Head = 'HEAD';
    case Trace = 'TRACE';
    case Connect = 'CONNECT';

    public function equals(string $method): bool
    {
        return strtoupper($this->value) === trim(strtoupper($method));
    }

    public static function fromString(string $method): self
    {
        return match (trim(strtoupper($method))) {
            'GET' => self::Get,
            'POST' => self::Post,
            'PUT' => self::Put,
            'DELETE' => self::Delete,
            'PATCH' => self::Patch,
            'OPTIONS' => self::Options,
            'HEAD' => self::Head,
            'TRACE' => self::Trace,
            'CONNECT' => self::Connect,
            default => throw new \InvalidArgumentException("Invalid method: {$method}"),
        };
    }
}
