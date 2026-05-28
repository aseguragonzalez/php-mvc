<?php

declare(strict_types=1);

namespace PhpMvc\Responses\Headers;

abstract readonly class Header
{
    public function __construct(public string $name, public string $value) {}

    public function __toString(): string
    {
        return "{$this->name}: {$this->value}";
    }

    public function equals(Header $header): bool
    {
        return $this->name === $header->name && $this->value === $header->value;
    }
}
