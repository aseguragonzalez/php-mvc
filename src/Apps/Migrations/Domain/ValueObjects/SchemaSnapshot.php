<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\ValueObjects;

final readonly class SchemaSnapshot
{
    /**
     * @param array<TableDefinition> $tables
     */
    private function __construct(
        public array $tables,
    ) {}

    /**
     * @param array<TableDefinition> $tables
     */
    public static function new(array $tables): self
    {
        return new self($tables);
    }

    public function equals(SchemaSnapshot $other): bool
    {
        if (count($this->tables) !== count($other->tables)) {
            return false;
        }

        foreach ($this->tables as $index => $table) {
            if (!$table->equals($other->tables[$index])) {
                return false;
            }
        }

        return true;
    }
}
