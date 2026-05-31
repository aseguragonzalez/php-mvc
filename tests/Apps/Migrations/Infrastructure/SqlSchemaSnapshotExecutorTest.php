<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Infrastructure;

use PhpMvc\Migrations\Domain\ValueObjects\SchemaSnapshot;
use PhpMvc\Migrations\Infrastructure\SqlSchemaSnapshotExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlSchemaSnapshotExecutorTest extends TestCase
{
    private MockObject&\PDO $pdo;
    private SqlSchemaSnapshotExecutor $service;

    /** @var array<\PDOStatement> */
    private array $prepareStatementQueue = [];

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->service = new SqlSchemaSnapshotExecutor($this->pdo);
        $this->prepareStatementQueue = [];
    }

    public function testCaptureReturnsEmptySnapshotWhenNoTablesExist(): void
    {
        $this->setupDatabaseName('test_db');
        $this->setupTables([]);

        $snapshot = $this->service->capture();

        $this->assertInstanceOf(SchemaSnapshot::class, $snapshot);
        $this->assertEmpty($snapshot->tables);
    }

    public function testCaptureReturnsSchemaWithTablesColumnsIndexesAndForeignKeys(): void
    {
        $this->setupDatabaseName('test_db');
        $this->setupTables(['users', 'posts']);
        $this->setupColumns('posts', [
            [
                'COLUMN_NAME' => 'id',
                'COLUMN_TYPE' => 'int(11)',
                'IS_NULLABLE' => 'NO',
                'COLUMN_DEFAULT' => null,
                'COLUMN_KEY' => 'PRI',
                'EXTRA' => 'auto_increment',
            ],
        ]);
        $this->setupIndexes('posts', []);
        $this->setupForeignKeys('posts', []);
        $this->setupColumns('users', [
            [
                'COLUMN_NAME' => 'id',
                'COLUMN_TYPE' => 'int(11)',
                'IS_NULLABLE' => 'NO',
                'COLUMN_DEFAULT' => null,
                'COLUMN_KEY' => 'PRI',
                'EXTRA' => 'auto_increment',
            ],
            [
                'COLUMN_NAME' => 'name',
                'COLUMN_TYPE' => 'varchar(255)',
                'IS_NULLABLE' => 'YES',
                'COLUMN_DEFAULT' => null,
                'COLUMN_KEY' => '',
                'EXTRA' => null,
            ],
        ]);
        $this->setupIndexes('users', [
            ['INDEX_NAME' => 'idx_name', 'COLUMN_NAME' => 'name', 'NON_UNIQUE' => '1'],
        ]);
        $this->setupForeignKeys('users', [
            [
                'CONSTRAINT_NAME' => 'fk_user',
                'COLUMN_NAME' => 'user_id',
                'REFERENCED_TABLE_NAME' => 'users',
                'REFERENCED_COLUMN_NAME' => 'id',
            ],
        ]);

        $snapshot = $this->service->capture();

        $this->assertCount(2, $snapshot->tables);
        $this->assertSame('posts', $snapshot->tables[0]->name);
        $this->assertSame('users', $snapshot->tables[1]->name);
        $this->assertCount(2, $snapshot->tables[1]->columns);
        $this->assertCount(1, $snapshot->tables[1]->indexes);
        $this->assertCount(1, $snapshot->tables[1]->foreignKeys);
    }

    public function testCaptureExcludesPrimaryKeyFromIndexes(): void
    {
        $this->setupDatabaseName('test_db');
        $this->setupTables(['users']);

        $this->setupColumns('users', [
            [
                'COLUMN_NAME' => 'id',
                'COLUMN_TYPE' => 'int(11)',
                'IS_NULLABLE' => 'NO',
                'COLUMN_DEFAULT' => null,
                'COLUMN_KEY' => 'PRI',
                'EXTRA' => 'auto_increment',
            ],
        ]);
        $this->setupIndexes('users', [
            ['INDEX_NAME' => 'PRIMARY', 'COLUMN_NAME' => 'id', 'NON_UNIQUE' => '0'],
            ['INDEX_NAME' => 'idx_name', 'COLUMN_NAME' => 'name', 'NON_UNIQUE' => '1'],
        ]);
        $this->setupForeignKeys('users', []);

        $snapshot = $this->service->capture();

        $this->assertCount(1, $snapshot->tables[0]->indexes);
        $this->assertSame('idx_name', $snapshot->tables[0]->indexes[0]->name);
    }

    public function testCaptureSortsTablesIndexesAndForeignKeysByName(): void
    {
        $this->setupDatabaseName('test_db');
        $this->setupTables(['alpha', 'beta', 'zebra']);
        $this->setupColumns('alpha', []);
        $this->setupIndexes('alpha', [
            ['INDEX_NAME' => 'idx_z', 'COLUMN_NAME' => 'z', 'NON_UNIQUE' => '1'],
            ['INDEX_NAME' => 'idx_a', 'COLUMN_NAME' => 'a', 'NON_UNIQUE' => '1'],
        ]);
        $this->setupForeignKeys('alpha', [
            [
                'CONSTRAINT_NAME' => 'fk_z',
                'COLUMN_NAME' => 'z_id',
                'REFERENCED_TABLE_NAME' => 'z',
                'REFERENCED_COLUMN_NAME' => 'id',
            ],
            [
                'CONSTRAINT_NAME' => 'fk_a',
                'COLUMN_NAME' => 'a_id',
                'REFERENCED_TABLE_NAME' => 'a',
                'REFERENCED_COLUMN_NAME' => 'id',
            ],
        ]);
        $this->setupColumns('beta', []);
        $this->setupIndexes('beta', []);
        $this->setupForeignKeys('beta', []);
        $this->setupColumns('zebra', []);
        $this->setupIndexes('zebra', []);
        $this->setupForeignKeys('zebra', []);

        $snapshot = $this->service->capture();

        $this->assertSame('alpha', $snapshot->tables[0]->name);
        $this->assertSame('beta', $snapshot->tables[1]->name);
        $this->assertSame('zebra', $snapshot->tables[2]->name);
        $this->assertSame('idx_a', $snapshot->tables[0]->indexes[0]->name);
        $this->assertSame('idx_z', $snapshot->tables[0]->indexes[1]->name);
        $this->assertSame('fk_a', $snapshot->tables[0]->foreignKeys[0]->name);
        $this->assertSame('fk_z', $snapshot->tables[0]->foreignKeys[1]->name);
    }

    public function testCaptureThrowsWhenDatabaseNameQueryFails(): void
    {
        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT DATABASE() as db_name')
            ->willReturn(false)
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to query database name');

        $this->service->capture();
    }

    public function testCaptureHandlesNoDatabaseSelected(): void
    {
        $dbStmt = $this->createMock(\PDOStatement::class);
        $this->pdo->expects($this->once())->method('query')->willReturn($dbStmt);
        $dbStmt->expects($this->once())->method('fetch')->with(\PDO::FETCH_ASSOC)->willReturn(false);

        $tablesStmt = $this->createMock(\PDOStatement::class);
        $tablesStmt->expects($this->once())->method('execute')->with(['database_name' => '']);
        $tablesStmt->expects($this->once())->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn([]);
        $this->pdo->expects($this->once())->method('prepare')->willReturn($tablesStmt);

        $snapshot = $this->service->capture();

        $this->assertSame([], $snapshot->tables);
    }

    private function setupDatabaseName(string $dbName): void
    {
        $dbStmt = $this->createMock(\PDOStatement::class);
        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT DATABASE() as db_name')
            ->willReturn($dbStmt)
        ;
        $dbStmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(['db_name' => $dbName])
        ;
    }

    /**
     * @param array<string> $tableNames
     */
    private function setupTables(array $tableNames): void
    {
        $tablesStmt = $this->createMock(\PDOStatement::class);
        $this->prepareStatementQueue[] = $tablesStmt;

        $tablesStmt->expects($this->once())
            ->method('execute')
            ->with(['database_name' => 'test_db'])
        ;
        sort($tableNames);
        $tablesData = array_map(fn (string $name) => ['TABLE_NAME' => $name], $tableNames);
        $tablesStmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($tablesData)
        ;

        $this->setupPrepareCallback();
    }

    /**
     * @param array<array<string, mixed>> $columns
     */
    private function setupColumns(string $tableName, array $columns): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $this->prepareStatementQueue[] = $stmt;

        $stmt->expects($this->once())
            ->method('execute')
            ->with(['database_name' => 'test_db', 'table_name' => $tableName])
        ;

        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($columns)
        ;
    }

    /**
     * @param array<array<string, mixed>> $indexes
     */
    private function setupIndexes(string $tableName, array $indexes): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $this->prepareStatementQueue[] = $stmt;

        $stmt->expects($this->once())
            ->method('execute')
            ->with(['database_name' => 'test_db', 'table_name' => $tableName])
        ;

        $fetchCount = count($indexes) + 1; // One call per row + one false to end loop
        $fetchReturns = array_values(array_merge($indexes, [false]));
        $stmt->expects($this->exactly($fetchCount))
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(...$fetchReturns)
        ;
    }

    /**
     * @param array<array<string, mixed>> $foreignKeys
     */
    private function setupForeignKeys(string $tableName, array $foreignKeys): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $this->prepareStatementQueue[] = $stmt;

        $stmt->expects($this->once())
            ->method('execute')
            ->with(['database_name' => 'test_db', 'table_name' => $tableName])
        ;

        $fetchCount = count($foreignKeys) + 1; // One call per row + one false to end loop
        $fetchReturns = array_values(array_merge($foreignKeys, [false]));
        $stmt->expects($this->exactly($fetchCount))
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(...$fetchReturns)
        ;
    }

    private function setupPrepareCallback(): void
    {
        $this->pdo->expects($this->atLeastOnce())
            ->method('prepare')
            ->willReturnCallback(function (string $sql): \PDOStatement {
                if (empty($this->prepareStatementQueue)) {
                    $fallbackStmt = $this->createMock(\PDOStatement::class);
                    $fallbackStmt->expects($this->any())->method('execute');
                    $fallbackStmt->expects($this->any())->method('fetch')->willReturn(false);
                    $fallbackStmt->expects($this->any())->method('fetchAll')->willReturn([]);

                    return $fallbackStmt;
                }

                return array_shift($this->prepareStatementQueue);
            })
        ;
    }
}
