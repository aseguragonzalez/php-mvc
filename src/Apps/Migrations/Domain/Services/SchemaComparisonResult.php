<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

final class SchemaComparisonResult
{
    /**
     * @param array<string> $differences
     */
    private function __construct(
        public readonly bool $areEqual,
        public readonly array $differences,
    ) {}

    /**
     * @param array<string> $differences
     */
    public static function new(bool $areEqual, array $differences = []): self
    {
        return new self($areEqual, $differences);
    }
}
