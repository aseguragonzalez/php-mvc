<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Domain;

use PhpMvc\Migrations\Domain\ValueObjects\ColumnDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\SchemaSnapshot;
use PhpMvc\Migrations\Domain\ValueObjects\TableDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SchemaSnapshotTest extends TestCase
{
    public function testItCreatesASchemaSnapshot(): void
    {
        $columns = [
            ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment'),
        ];
        $indexes = [];
        $foreignKeys = [];
        $table = TableDefinition::new('users', $columns, $indexes, $foreignKeys);
        $tables = [$table];

        $snapshot = SchemaSnapshot::new($tables);

        $this->assertCount(1, $snapshot->tables);
        $this->assertSame($table, $snapshot->tables[0]);
    }

    public function testItCreatesASchemaSnapshotWithMultipleTables(): void
    {
        $columns1 = [
            ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment'),
        ];
        $table1 = TableDefinition::new('users', $columns1, [], []);

        $columns2 = [
            ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment'),
            ColumnDefinition::new('user_id', 'int(11)', false, null, false, null),
        ];
        $table2 = TableDefinition::new('posts', $columns2, [], []);

        $snapshot = SchemaSnapshot::new([$table1, $table2]);

        $this->assertCount(2, $snapshot->tables);
    }

    public function testEqualsReturnsTrueForIdenticalSnapshots(): void
    {
        $columns = [
            ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment'),
        ];
        $table = TableDefinition::new('users', $columns, [], []);

        $snapshot1 = SchemaSnapshot::new([$table]);
        $snapshot2 = SchemaSnapshot::new([$table]);

        $this->assertTrue($snapshot1->equals($snapshot2));
    }

    public function testEqualsReturnsFalseWhenTableCountDiffers(): void
    {
        $columns = [
            ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment'),
        ];
        $table1 = TableDefinition::new('users', $columns, [], []);
        $table2 = TableDefinition::new('posts', $columns, [], []);

        $snapshot1 = SchemaSnapshot::new([$table1]);
        $snapshot2 = SchemaSnapshot::new([$table1, $table2]);

        $this->assertFalse($snapshot1->equals($snapshot2));
    }

    public function testEqualsReturnsFalseWhenTableContentDiffers(): void
    {
        $columns1 = [
            ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment'),
        ];
        $columns2 = [
            ColumnDefinition::new('id', 'bigint(20)', false, null, true, 'auto_increment'),
        ];
        $table1 = TableDefinition::new('users', $columns1, [], []);
        $table2 = TableDefinition::new('users', $columns2, [], []);

        $snapshot1 = SchemaSnapshot::new([$table1]);
        $snapshot2 = SchemaSnapshot::new([$table2]);

        $this->assertFalse($snapshot1->equals($snapshot2));
    }
}
