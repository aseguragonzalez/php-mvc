<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Domain;

use PhpMvc\Migrations\Domain\ValueObjects\ColumnDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\ForeignKeyDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\IndexDefinition;
use PhpMvc\Migrations\Domain\ValueObjects\TableDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValueObjectsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // ColumnDefinition::equals
    // -------------------------------------------------------------------------

    public function testColumnDefinitionEqualsReturnsTrueForIdenticalColumns(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $col2 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');

        $this->assertTrue($col1->equals($col2));
    }

    public function testColumnDefinitionEqualsReturnsFalseWhenNameDiffers(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $col2 = ColumnDefinition::new('uid', 'int(11)', false, null, true, 'auto_increment');

        $this->assertFalse($col1->equals($col2));
    }

    public function testColumnDefinitionEqualsReturnsFalseWhenTypeDiffers(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $col2 = ColumnDefinition::new('id', 'bigint(20)', false, null, true, 'auto_increment');

        $this->assertFalse($col1->equals($col2));
    }

    public function testColumnDefinitionEqualsReturnsFalseWhenNullabilityDiffers(): void
    {
        $col1 = ColumnDefinition::new('name', 'varchar(255)', false, null, false, null);
        $col2 = ColumnDefinition::new('name', 'varchar(255)', true, null, false, null);

        $this->assertFalse($col1->equals($col2));
    }

    public function testColumnDefinitionEqualsReturnsFalseWhenDefaultValueDiffers(): void
    {
        $col1 = ColumnDefinition::new('status', 'varchar(20)', false, 'active', false, null);
        $col2 = ColumnDefinition::new('status', 'varchar(20)', false, 'inactive', false, null);

        $this->assertFalse($col1->equals($col2));
    }

    public function testColumnDefinitionEqualsReturnsFalseWhenPrimaryKeyDiffers(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, null);
        $col2 = ColumnDefinition::new('id', 'int(11)', false, null, false, null);

        $this->assertFalse($col1->equals($col2));
    }

    public function testColumnDefinitionEqualsReturnsFalseWhenExtraDiffers(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $col2 = ColumnDefinition::new('id', 'int(11)', false, null, true, null);

        $this->assertFalse($col1->equals($col2));
    }

    // -------------------------------------------------------------------------
    // ForeignKeyDefinition::equals
    // -------------------------------------------------------------------------

    public function testForeignKeyDefinitionEqualsReturnsTrueForIdenticalKeys(): void
    {
        $fk1 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);

        $this->assertTrue($fk1->equals($fk2));
    }

    public function testForeignKeyDefinitionEqualsReturnsFalseWhenNameDiffers(): void
    {
        $fk1 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_other', 'orders', ['user_id'], 'users', ['id']);

        $this->assertFalse($fk1->equals($fk2));
    }

    public function testForeignKeyDefinitionEqualsReturnsFalseWhenTableNameDiffers(): void
    {
        $fk1 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_user', 'invoices', ['user_id'], 'users', ['id']);

        $this->assertFalse($fk1->equals($fk2));
    }

    public function testForeignKeyDefinitionEqualsReturnsFalseWhenColumnsDiffer(): void
    {
        $fk1 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_user', 'orders', ['customer_id'], 'users', ['id']);

        $this->assertFalse($fk1->equals($fk2));
    }

    public function testForeignKeyDefinitionEqualsReturnsFalseWhenReferencedTableDiffers(): void
    {
        $fk1 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'accounts', ['id']);

        $this->assertFalse($fk1->equals($fk2));
    }

    public function testForeignKeyDefinitionEqualsReturnsFalseWhenReferencedColumnsDiffer(): void
    {
        $fk1 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_user', 'orders', ['user_id'], 'users', ['uuid']);

        $this->assertFalse($fk1->equals($fk2));
    }

    // -------------------------------------------------------------------------
    // IndexDefinition::equals
    // -------------------------------------------------------------------------

    public function testIndexDefinitionEqualsReturnsTrueForIdenticalIndexes(): void
    {
        $idx1 = IndexDefinition::new('idx_email', 'users', ['email'], true);
        $idx2 = IndexDefinition::new('idx_email', 'users', ['email'], true);

        $this->assertTrue($idx1->equals($idx2));
    }

    public function testIndexDefinitionEqualsReturnsFalseWhenNameDiffers(): void
    {
        $idx1 = IndexDefinition::new('idx_email', 'users', ['email'], true);
        $idx2 = IndexDefinition::new('idx_mail', 'users', ['email'], true);

        $this->assertFalse($idx1->equals($idx2));
    }

    public function testIndexDefinitionEqualsReturnsFalseWhenTableNameDiffers(): void
    {
        $idx1 = IndexDefinition::new('idx_email', 'users', ['email'], true);
        $idx2 = IndexDefinition::new('idx_email', 'accounts', ['email'], true);

        $this->assertFalse($idx1->equals($idx2));
    }

    public function testIndexDefinitionEqualsReturnsFalseWhenColumnsDiffer(): void
    {
        $idx1 = IndexDefinition::new('idx_email', 'users', ['email'], true);
        $idx2 = IndexDefinition::new('idx_email', 'users', ['username'], true);

        $this->assertFalse($idx1->equals($idx2));
    }

    public function testIndexDefinitionEqualsReturnsFalseWhenUniquenessDiffers(): void
    {
        $idx1 = IndexDefinition::new('idx_email', 'users', ['email'], true);
        $idx2 = IndexDefinition::new('idx_email', 'users', ['email'], false);

        $this->assertFalse($idx1->equals($idx2));
    }

    // -------------------------------------------------------------------------
    // TableDefinition::equals
    // -------------------------------------------------------------------------

    public function testTableDefinitionEqualsReturnsTrueForIdenticalTables(): void
    {
        $col = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $idx = IndexDefinition::new('idx_id', 'users', ['id'], true);
        $fk = ForeignKeyDefinition::new('fk_role', 'users', ['role_id'], 'roles', ['id']);

        $table1 = TableDefinition::new('users', [$col], [$idx], [$fk]);
        $table2 = TableDefinition::new('users', [$col], [$idx], [$fk]);

        $this->assertTrue($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenNameDiffers(): void
    {
        $col = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $table1 = TableDefinition::new('users', [$col], [], []);
        $table2 = TableDefinition::new('accounts', [$col], [], []);

        $this->assertFalse($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenColumnCountDiffers(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $col2 = ColumnDefinition::new('name', 'varchar(255)', false, null, false, null);

        $table1 = TableDefinition::new('users', [$col1], [], []);
        $table2 = TableDefinition::new('users', [$col1, $col2], [], []);

        $this->assertFalse($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenColumnContentDiffers(): void
    {
        $col1 = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $col2 = ColumnDefinition::new('id', 'bigint(20)', false, null, true, 'auto_increment');

        $table1 = TableDefinition::new('users', [$col1], [], []);
        $table2 = TableDefinition::new('users', [$col2], [], []);

        $this->assertFalse($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenIndexCountDiffers(): void
    {
        $col = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $idx = IndexDefinition::new('idx_id', 'users', ['id'], true);

        $table1 = TableDefinition::new('users', [$col], [], []);
        $table2 = TableDefinition::new('users', [$col], [$idx], []);

        $this->assertFalse($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenIndexContentDiffers(): void
    {
        $col = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $idx1 = IndexDefinition::new('idx_id', 'users', ['id'], true);
        $idx2 = IndexDefinition::new('idx_id', 'users', ['id'], false);

        $table1 = TableDefinition::new('users', [$col], [$idx1], []);
        $table2 = TableDefinition::new('users', [$col], [$idx2], []);

        $this->assertFalse($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenForeignKeyCountDiffers(): void
    {
        $col = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $fk = ForeignKeyDefinition::new('fk_role', 'users', ['role_id'], 'roles', ['id']);

        $table1 = TableDefinition::new('users', [$col], [], []);
        $table2 = TableDefinition::new('users', [$col], [], [$fk]);

        $this->assertFalse($table1->equals($table2));
    }

    public function testTableDefinitionEqualsReturnsFalseWhenForeignKeyContentDiffers(): void
    {
        $col = ColumnDefinition::new('id', 'int(11)', false, null, true, 'auto_increment');
        $fk1 = ForeignKeyDefinition::new('fk_role', 'users', ['role_id'], 'roles', ['id']);
        $fk2 = ForeignKeyDefinition::new('fk_role', 'users', ['role_id'], 'groups', ['id']);

        $table1 = TableDefinition::new('users', [$col], [], [$fk1]);
        $table2 = TableDefinition::new('users', [$col], [], [$fk2]);

        $this->assertFalse($table1->equals($table2));
    }
}
