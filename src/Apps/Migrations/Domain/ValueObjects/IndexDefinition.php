<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\ValueObjects;

final readonly class IndexDefinition
{
    /**
     * @param array<string> $columns
     */
    private function __construct(
        public string $name,
        public string $tableName,
        public array $columns,
        public bool $isUnique,
    ) {}

    /**
     * @param array<string> $columns
     */
    public static function new(string $name, string $tableName, array $columns, bool $isUnique): self
    {
        return new self($name, $tableName, $columns, $isUnique);
    }

    public function equals(IndexDefinition $other): bool
    {
        return $this->name === $other->name
            && $this->tableName === $other->tableName
            && $this->columns === $other->columns
            && $this->isUnique === $other->isUnique;
    }
}
