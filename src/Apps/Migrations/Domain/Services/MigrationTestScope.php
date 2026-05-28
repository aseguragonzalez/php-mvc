<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

/**
 * Holds migration services (snapshot, executor, rollback) bound to a specific database.
 * Used when testing migrations against a temporary clone database.
 */
final readonly class MigrationTestScope
{
    public function __construct(
        public SchemaSnapshotExecutor $schemaSnapshotExecutor,
        public TestMigrationExecutor $testMigrationExecutor,
        public RollbackExecutor $rollbackExecutor,
    ) {}
}
