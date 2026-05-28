<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

use PhpMvc\Migrations\Domain\ValueObjects\ColumnDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\ForeignKeyDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\IndexDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\SchemaSnapshot;
use PhpMvc\Migrations\Domain\ValueObjects\TableDefinition;

final readonly class SchemaComparatorHandler implements SchemaComparator
{
    public function compare(SchemaSnapshot $initial, SchemaSnapshot $final): SchemaComparisonResult
    {
        $differences = [];

        $initialTables = $this->indexTablesByName($initial->tables);
        $finalTables = $this->indexTablesByName($final->tables);

        // Check for missing or extra tables
        $initialTableNames = array_keys($initialTables);
        $finalTableNames = array_keys($finalTables);

        $missingTables = array_diff($initialTableNames, $finalTableNames);
        $extraTables = array_diff($finalTableNames, $initialTableNames);

        foreach ($missingTables as $tableName) {
            $differences[] = "Table '{$tableName}' was removed";
        }

        foreach ($extraTables as $tableName) {
            $differences[] = "Table '{$tableName}' was added";
        }

        // Compare common tables
        $commonTables = array_intersect($initialTableNames, $finalTableNames);
        foreach ($commonTables as $tableName) {
            $tableDifferences = $this->compareTables($initialTables[$tableName], $finalTables[$tableName]);
            $differences = array_merge($differences, $tableDifferences);
        }

        return SchemaComparisonResult::new(
            areEqual: empty($differences),
            differences: $differences,
        );
    }

    /**
     * @param array<TableDefinition> $tables
     *
     * @return array<string, TableDefinition>
     */
    private function indexTablesByName(array $tables): array
    {
        $indexed = [];
        foreach ($tables as $table) {
            $indexed[$table->name] = $table;
        }

        return $indexed;
    }

    /**
     * @return array<string>
     */
    private function compareTables(TableDefinition $initial, TableDefinition $final): array
    {
        $differences = [];

        // Compare columns
        $columnDifferences = $this->compareColumns($initial, $final);
        $differences = array_merge($differences, $columnDifferences);

        // Compare indexes
        $indexDifferences = $this->compareIndexes($initial, $final);
        $differences = array_merge($differences, $indexDifferences);

        // Compare foreign keys
        $fkDifferences = $this->compareForeignKeys($initial, $final);

        return array_merge($differences, $fkDifferences);
    }

    /**
     * @return array<string>
     */
    private function compareColumns(TableDefinition $initial, TableDefinition $final): array
    {
        $differences = [];

        $initialColumns = $this->indexColumnsByName($initial->columns);
        $finalColumns = $this->indexColumnsByName($final->columns);

        $initialColumnNames = array_keys($initialColumns);
        $finalColumnNames = array_keys($finalColumns);

        $missingColumns = array_diff($initialColumnNames, $finalColumnNames);
        $extraColumns = array_diff($finalColumnNames, $initialColumnNames);

        foreach ($missingColumns as $columnName) {
            $differences[] = "Table '{$initial->name}': Column '{$columnName}' was removed";
        }

        foreach ($extraColumns as $columnName) {
            $differences[] = "Table '{$initial->name}': Column '{$columnName}' was added";
        }

        $commonColumns = array_intersect($initialColumnNames, $finalColumnNames);
        foreach ($commonColumns as $columnName) {
            $initialColumn = $initialColumns[$columnName];
            $finalColumn = $finalColumns[$columnName];

            if ($initialColumn->type !== $finalColumn->type) {
                $differences[] = "Table '{$initial->name}': Column '{$columnName}' "
                    ."type changed from '{$initialColumn->type}' to '{$finalColumn->type}'";
            }

            if ($initialColumn->isNullable !== $finalColumn->isNullable) {
                $nullableStatus = $initialColumn->isNullable ? 'nullable' : 'not nullable';
                $newNullableStatus = $finalColumn->isNullable ? 'nullable' : 'not nullable';
                $differences[] = "Table '{$initial->name}': Column '{$columnName}' "
                    ."changed from {$nullableStatus} to {$newNullableStatus}";
            }

            if ($initialColumn->defaultValue !== $finalColumn->defaultValue) {
                $initialDefault = $initialColumn->defaultValue ?? 'NULL';
                $finalDefault = $finalColumn->defaultValue ?? 'NULL';
                $differences[] = "Table '{$initial->name}': Column '{$columnName}' "
                    ."default changed from {$initialDefault} to {$finalDefault}";
            }

            if ($initialColumn->isPrimaryKey !== $finalColumn->isPrimaryKey) {
                $pkStatus = $initialColumn->isPrimaryKey ? 'primary key' : 'not primary key';
                $newPkStatus = $finalColumn->isPrimaryKey ? 'primary key' : 'not primary key';
                $differences[] = "Table '{$initial->name}': Column '{$columnName}' "
                    ."changed from {$pkStatus} to {$newPkStatus}";
            }
        }

        return $differences;
    }

    /**
     * @param array<ColumnDefinition> $columns
     *
     * @return array<string, ColumnDefinition>
     */
    private function indexColumnsByName(array $columns): array
    {
        $indexed = [];
        foreach ($columns as $column) {
            $indexed[$column->name] = $column;
        }

        return $indexed;
    }

    /**
     * @return array<string>
     */
    private function compareIndexes(TableDefinition $initial, TableDefinition $final): array
    {
        $differences = [];

        $initialIndexes = $this->indexIndexesByName($initial->indexes);
        $finalIndexes = $this->indexIndexesByName($final->indexes);

        $initialIndexNames = array_keys($initialIndexes);
        $finalIndexNames = array_keys($finalIndexes);

        $missingIndexes = array_diff($initialIndexNames, $finalIndexNames);
        $extraIndexes = array_diff($finalIndexNames, $initialIndexNames);

        foreach ($missingIndexes as $indexName) {
            $differences[] = "Table '{$initial->name}': Index '{$indexName}' was removed";
        }

        foreach ($extraIndexes as $indexName) {
            $differences[] = "Table '{$initial->name}': Index '{$indexName}' was added";
        }

        $commonIndexes = array_intersect($initialIndexNames, $finalIndexNames);
        foreach ($commonIndexes as $indexName) {
            $initialIndex = $initialIndexes[$indexName];
            $finalIndex = $finalIndexes[$indexName];

            if ($initialIndex->isUnique !== $finalIndex->isUnique) {
                $uniqueStatus = $initialIndex->isUnique ? 'unique' : 'non-unique';
                $newUniqueStatus = $finalIndex->isUnique ? 'unique' : 'non-unique';
                $differences[] = "Table '{$initial->name}': Index '{$indexName}' "
                    ."changed from {$uniqueStatus} to {$newUniqueStatus}";
            }

            if ($initialIndex->columns !== $finalIndex->columns) {
                $initialColumns = implode(', ', $initialIndex->columns);
                $finalColumns = implode(', ', $finalIndex->columns);
                $differences[] = "Table '{$initial->name}': Index '{$indexName}' "
                    ."columns changed from [{$initialColumns}] to [{$finalColumns}]";
            }
        }

        return $differences;
    }

    /**
     * @param array<IndexDefinition> $indexes
     *
     * @return array<string, IndexDefinition>
     */
    private function indexIndexesByName(array $indexes): array
    {
        $indexed = [];
        foreach ($indexes as $index) {
            $indexed[$index->name] = $index;
        }

        return $indexed;
    }

    /**
     * @return array<string>
     */
    private function compareForeignKeys(TableDefinition $initial, TableDefinition $final): array
    {
        $differences = [];

        $initialFks = $this->indexForeignKeysByName($initial->foreignKeys);
        $finalFks = $this->indexForeignKeysByName($final->foreignKeys);

        $initialFkNames = array_keys($initialFks);
        $finalFkNames = array_keys($finalFks);

        $missingFks = array_diff($initialFkNames, $finalFkNames);
        $extraFks = array_diff($finalFkNames, $initialFkNames);

        foreach ($missingFks as $fkName) {
            $differences[] = "Table '{$initial->name}': Foreign key '{$fkName}' was removed";
        }

        foreach ($extraFks as $fkName) {
            $differences[] = "Table '{$initial->name}': Foreign key '{$fkName}' was added";
        }

        $commonFks = array_intersect($initialFkNames, $finalFkNames);
        foreach ($commonFks as $fkName) {
            $initialFk = $initialFks[$fkName];
            $finalFk = $finalFks[$fkName];

            if ($initialFk->referencedTableName !== $finalFk->referencedTableName) {
                $differences[] = "Table '{$initial->name}': Foreign key '{$fkName}' "
                    ."referenced table changed from '{$initialFk->referencedTableName}' "
                    ."to '{$finalFk->referencedTableName}'";
            }

            if ($initialFk->columns !== $finalFk->columns) {
                $initialColumns = implode(', ', $initialFk->columns);
                $finalColumns = implode(', ', $finalFk->columns);
                $differences[] = "Table '{$initial->name}': Foreign key '{$fkName}' columns "
                    ."changed from [{$initialColumns}] to [{$finalColumns}]";
            }

            if ($initialFk->referencedColumns !== $finalFk->referencedColumns) {
                $initialRefColumns = implode(', ', $initialFk->referencedColumns);
                $finalRefColumns = implode(', ', $finalFk->referencedColumns);
                $differences[] = "Table '{$initial->name}': Foreign key '{$fkName}' referenced "
                    ."columns changed from [{$initialRefColumns}] to [{$finalRefColumns}]";
            }
        }

        return $differences;
    }

    /**
     * @param array<ForeignKeyDefinition> $foreignKeys
     *
     * @return array<string, ForeignKeyDefinition>
     */
    private function indexForeignKeysByName(array $foreignKeys): array
    {
        $indexed = [];
        foreach ($foreignKeys as $fk) {
            $indexed[$fk->name] = $fk;
        }

        return $indexed;
    }
}
