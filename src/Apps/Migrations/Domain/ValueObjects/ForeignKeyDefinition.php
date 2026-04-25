<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\ValueObjects;

final readonly class ForeignKeyDefinition
{
    /**
     * @param array<string> $columns
     * @param array<string> $referencedColumns
     */
    private function __construct(
        public string $name,
        public string $tableName,
        public array $columns,
        public string $referencedTableName,
        public array $referencedColumns,
    ) {}

    /**
     * @param array<string> $columns
     * @param array<string> $referencedColumns
     */
    public static function new(
        string $name,
        string $tableName,
        array $columns,
        string $referencedTableName,
        array $referencedColumns,
    ): self {
        return new self($name, $tableName, $columns, $referencedTableName, $referencedColumns);
    }

    public function equals(ForeignKeyDefinition $other): bool
    {
        return $this->name === $other->name
            && $this->tableName === $other->tableName
            && $this->columns === $other->columns
            && $this->referencedTableName === $other->referencedTableName
            && $this->referencedColumns === $other->referencedColumns;
    }
}
