<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Domain;

use PhpMvc\Migrations\Domain\Services\MigrationTestScope;
use PhpMvc\Migrations\Domain\Services\RollbackExecutor;
use PhpMvc\Migrations\Domain\Services\SchemaSnapshotExecutor;
use PhpMvc\Migrations\Domain\Services\TestMigrationExecutor;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationTestScopeTest extends TestCase
{
    public function testScopeExposesSnapshotExecutorTestExecutorAndRollbackExecutor(): void
    {
        $schemaSnapshotExecutor = $this->createStub(SchemaSnapshotExecutor::class);
        $testMigrationExecutor = $this->createStub(TestMigrationExecutor::class);
        $rollbackExecutor = $this->createStub(RollbackExecutor::class);

        $scope = new MigrationTestScope(
            schemaSnapshotExecutor: $schemaSnapshotExecutor,
            testMigrationExecutor: $testMigrationExecutor,
            rollbackExecutor: $rollbackExecutor,
        );

        $this->assertSame($schemaSnapshotExecutor, $scope->schemaSnapshotExecutor);
        $this->assertSame($testMigrationExecutor, $scope->testMigrationExecutor);
        $this->assertSame($rollbackExecutor, $scope->rollbackExecutor);
    }
}
