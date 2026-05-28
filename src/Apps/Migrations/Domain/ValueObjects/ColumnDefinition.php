<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\ValueObjects;

final readonly class ColumnDefinition
{
    private function __construct(
        public string $name,
        public string $type,
        public bool $isNullable,
        public ?string $defaultValue,
        public bool $isPrimaryKey,
        public ?string $extra,
    ) {}

    public static function new(
        string $name,
        string $type,
        bool $isNullable,
        ?string $defaultValue,
        bool $isPrimaryKey,
        ?string $extra,
    ): self {
        return new self($name, $type, $isNullable, $defaultValue, $isPrimaryKey, $extra);
    }

    public function equals(ColumnDefinition $other): bool
    {
        return $this->name === $other->name
            && $this->type === $other->type
            && $this->isNullable === $other->isNullable
            && $this->defaultValue === $other->defaultValue
            && $this->isPrimaryKey === $other->isPrimaryKey
            && $this->extra === $other->extra;
    }
}
