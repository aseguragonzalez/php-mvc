<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Services;

interface DatabaseBackupManager
{
    public function backup(): string;

    public function restore(string $backupFilePath): void;

    /**
     * Create a temporary database from a backup file and return its name.
     * The backup is restored into the new database; the original database is not modified.
     */
    public function createTestDatabaseFromBackup(string $backupFilePath): string;

    /**
     * Drop a temporary test database created by createTestDatabaseFromBackup.
     */
    public function destroyTestDatabase(string $testDatabaseName): void;
}
