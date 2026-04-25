<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\ValueObjects;

final readonly class TableDefinition
{
    /**
     * @param array<ColumnDefinition>     $columns
     * @param array<IndexDefinition>      $indexes
     * @param array<ForeignKeyDefinition> $foreignKeys
     */
    private function __construct(
        public string $name,
        public array $columns,
        public array $indexes,
        public array $foreignKeys,
    ) {}

    /**
     * @param array<ColumnDefinition>     $columns
     * @param array<IndexDefinition>      $indexes
     * @param array<ForeignKeyDefinition> $foreignKeys
     */
    public static function new(string $name, array $columns, array $indexes, array $foreignKeys): self
    {
        return new self($name, $columns, $indexes, $foreignKeys);
    }

    public function equals(TableDefinition $other): bool
    {
        if ($this->name !== $other->name) {
            return false;
        }

        if (count($this->columns) !== count($other->columns)) {
            return false;
        }

        foreach ($this->columns as $index => $column) {
            if (!$column->equals($other->columns[$index])) {
                return false;
            }
        }

        if (count($this->indexes) !== count($other->indexes)) {
            return false;
        }

        foreach ($this->indexes as $index => $idx) {
            if (!$idx->equals($other->indexes[$index])) {
                return false;
            }
        }

        if (count($this->foreignKeys) !== count($other->foreignKeys)) {
            return false;
        }

        foreach ($this->foreignKeys as $index => $fk) {
            if (!$fk->equals($other->foreignKeys[$index])) {
                return false;
            }
        }

        return true;
    }
}
