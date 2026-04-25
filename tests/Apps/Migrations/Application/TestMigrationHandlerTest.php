<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Migrations\Application;

use AlfonsoSG\Mvc\Migrations\Application\TestMigrationCommand;
use AlfonsoSG\Mvc\Migrations\Application\TestMigrationHandler;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;
use AlfonsoSG\Mvc\Migrations\Domain\Services\DatabaseBackupManager;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationFileManager;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationTestScope;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationTestScopeFactory;
use AlfonsoSG\Mvc\Migrations\Domain\Services\RollbackExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\Services\SchemaComparator;
use AlfonsoSG\Mvc\Migrations\Domain\Services\SchemaComparisonResult;
use AlfonsoSG\Mvc\Migrations\Domain\Services\SchemaSnapshotExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\Services\TestMigrationExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\ValueObjects\SchemaSnapshot;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TestMigrationHandlerTest extends TestCase
{
    private MigrationFileManager&Stub $migrationFileManager;
    private DatabaseBackupManager&Stub $databaseBackupManager;
    private MigrationTestScopeFactory&Stub $scopeFactory;
    private SchemaComparator&Stub $schemaComparator;
    private TestMigrationHandler $service;

    protected function setUp(): void
    {
        $this->migrationFileManager = $this->createStub(MigrationFileManager::class);
        $this->databaseBackupManager = $this->createStub(DatabaseBackupManager::class);
        $this->scopeFactory = $this->createStub(MigrationTestScopeFactory::class);
        $this->schemaComparator = $this->createStub(SchemaComparator::class);

        $this->service = new TestMigrationHandler(
            $this->migrationFileManager,
            $this->databaseBackupManager,
            $this->scopeFactory,
            $this->schemaComparator,
        );
    }

    public function testExecuteThrowsExceptionWhenMigrationNotFound(): void
    {
        $command = new TestMigrationCommand('non_existent', '/test/migrations');
        $this->migrationFileManager->method('getMigrationByName')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Migration 'non_existent' not found");

        $this->service->execute($command);
    }

    public function testExecuteSuccessfullyTestsMigrationWhenSchemasMatch(): void
    {
        $migration = Migration::new('test_migration', []);
        $command = new TestMigrationCommand('test_migration', '/test/migrations');
        $initialSnapshot = SchemaSnapshot::new([]);
        $finalSnapshot = SchemaSnapshot::new([]);
        $comparisonResult = SchemaComparisonResult::new(true);
        $backupFilePath = '/tmp/backup.sql';
        $testDbName = 'test_abc123';
        $schemaSnapshotExecutor = $this->createMock(SchemaSnapshotExecutor::class);
        $schemaSnapshotExecutor
            ->expects($this->exactly(2))
            ->method('capture')
            ->willReturnOnConsecutiveCalls($initialSnapshot, $finalSnapshot)
        ;
        $testMigrationExecutor = $this->createMock(TestMigrationExecutor::class);
        $testMigrationExecutor->expects($this->once())->method('execute');
        $rollbackExecutor = $this->createMock(RollbackExecutor::class);
        $rollbackExecutor->expects($this->once())->method('rollback');
        $scope = new MigrationTestScope(
            schemaSnapshotExecutor: $schemaSnapshotExecutor,
            testMigrationExecutor: $testMigrationExecutor,
            rollbackExecutor: $rollbackExecutor,
        );
        $this->migrationFileManager->method('getMigrationByName')->willReturn($migration);
        $this->databaseBackupManager->method('backup')->willReturn($backupFilePath);
        $this->databaseBackupManager->method('createTestDatabaseFromBackup')->willReturn($testDbName);
        $this->databaseBackupManager->method('destroyTestDatabase');
        $this->scopeFactory->method('createScope')->willReturn($scope);
        $this->schemaComparator->method('compare')->willReturn($comparisonResult);

        $this->service->execute($command);
    }

    public function testExecuteThrowsExceptionWhenSchemasDiffer(): void
    {
        $migration = Migration::new('test_migration', []);
        $command = new TestMigrationCommand('test_migration', '/test/migrations');
        $initialSnapshot = SchemaSnapshot::new([]);
        $finalSnapshot = SchemaSnapshot::new([]);
        $comparisonResult = SchemaComparisonResult::new(false, ['Table users was added']);
        $backupFilePath = '/tmp/backup.sql';
        $testDbName = 'test_abc123';
        $schemaSnapshotExecutor = $this->createStub(SchemaSnapshotExecutor::class);
        $schemaSnapshotExecutor->method('capture')
            ->willReturnOnConsecutiveCalls($initialSnapshot, $finalSnapshot)
        ;
        $scope = new MigrationTestScope(
            schemaSnapshotExecutor: $schemaSnapshotExecutor,
            testMigrationExecutor: $this->createStub(TestMigrationExecutor::class),
            rollbackExecutor: $this->createStub(RollbackExecutor::class),
        );
        $this->migrationFileManager->method('getMigrationByName')->willReturn($migration);
        $this->databaseBackupManager->method('backup')->willReturn($backupFilePath);
        $this->databaseBackupManager->method('createTestDatabaseFromBackup')->willReturn($testDbName);
        $this->databaseBackupManager->method('destroyTestDatabase');
        $this->scopeFactory->method('createScope')->willReturn($scope);
        $this->schemaComparator->method('compare')->willReturn($comparisonResult);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration rollback test failed. Schema differences detected.');

        $this->service->execute($command);
    }

    public function testExecuteDestroysTestDatabaseOnError(): void
    {
        $migration = Migration::new('test_migration', []);
        $command = new TestMigrationCommand('test_migration', '/test/migrations');
        $backupFilePath = '/tmp/backup.sql';
        $testDbName = 'test_abc123';

        $schemaSnapshotExecutor = $this->createStub(SchemaSnapshotExecutor::class);
        $schemaSnapshotExecutor->method('capture')
            ->willThrowException(new \RuntimeException('Database error'))
        ;
        $scope = new MigrationTestScope(
            schemaSnapshotExecutor: $schemaSnapshotExecutor,
            testMigrationExecutor: $this->createStub(TestMigrationExecutor::class),
            rollbackExecutor: $this->createStub(RollbackExecutor::class),
        );

        $this->migrationFileManager->method('getMigrationByName')->willReturn($migration);
        $this->databaseBackupManager->method('backup')->willReturn($backupFilePath);
        $this->databaseBackupManager->method('createTestDatabaseFromBackup')->willReturn($testDbName);
        $this->databaseBackupManager->method('destroyTestDatabase');
        $this->scopeFactory->method('createScope')->willReturn($scope);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->service->execute($command);
    }

    public function testExecuteDestroysTestDatabaseWhenMigrationExecutionFails(): void
    {
        $migration = Migration::new('test_migration', []);
        $command = new TestMigrationCommand('test_migration', '/test/migrations');
        $initialSnapshot = SchemaSnapshot::new([]);
        $backupFilePath = '/tmp/backup.sql';
        $testDbName = 'test_abc123';

        $schemaSnapshotExecutor = $this->createStub(SchemaSnapshotExecutor::class);
        $schemaSnapshotExecutor->method('capture')->willReturn($initialSnapshot);
        $testMigrationExecutor = $this->createStub(TestMigrationExecutor::class);
        $testMigrationExecutor
            ->method('execute')
            ->willThrowException(new \RuntimeException('Migration execution failed'))
        ;
        $scope = new MigrationTestScope(
            schemaSnapshotExecutor: $schemaSnapshotExecutor,
            testMigrationExecutor: $testMigrationExecutor,
            rollbackExecutor: $this->createStub(RollbackExecutor::class),
        );
        $this->migrationFileManager->method('getMigrationByName')->willReturn($migration);
        $this->databaseBackupManager->method('backup')->willReturn($backupFilePath);
        $this->databaseBackupManager->method('createTestDatabaseFromBackup')->willReturn($testDbName);
        $this->databaseBackupManager->method('destroyTestDatabase');
        $this->scopeFactory->method('createScope')->willReturn($scope);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration execution failed');

        $this->service->execute($command);
    }
}
