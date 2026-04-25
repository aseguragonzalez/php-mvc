<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

/**
 * Writes timestamped migration folders for the default SQL-backed background_tasks table.
 *
 * @internal
 */
final class BackgroundTasksDefaultMigrationWriter
{
    private const UP_STUB = 'background-tasks-default-up.sql.stub';

    private const DOWN_STUB = 'background-tasks-default-down.sql.stub';

    /**
     * Creates a migration that applies the default background_tasks table; rollback drops it.
     *
     * @return string Absolute path to the created migration directory
     */
    public static function createEnableMigration(string $leafMigrationsDir): string
    {
        return self::writeTimestampedPair($leafMigrationsDir, self::UP_STUB, self::DOWN_STUB);
    }

    /**
     * Creates a migration that drops background_tasks; rollback recreates it.
     *
     * @return string Absolute path to the created migration directory
     */
    public static function createDisableMigration(string $leafMigrationsDir): string
    {
        return self::writeTimestampedPair($leafMigrationsDir, self::DOWN_STUB, self::UP_STUB);
    }

    private static function stubsDirectory(): string
    {
        return __DIR__.'/stubs';
    }

    private static function writeTimestampedPair(
        string $leafMigrationsDir,
        string $forwardStub,
        string $rollbackStub,
    ): string {
        $timestamp = date('YmdHis');
        $migrationDir = rtrim($leafMigrationsDir, '/').'/'.$timestamp;

        if (!mkdir($migrationDir, 0o755, true)) {
            throw new \RuntimeException("Failed to create migration directory: {$migrationDir}");
        }

        $forward = self::readStub($forwardStub);
        $rollback = self::readStub($rollbackStub);

        file_put_contents($migrationDir.'/0001_migration.sql', $forward);
        file_put_contents($migrationDir.'/0001_migration.rollback.sql', $rollback);

        return $migrationDir;
    }

    private static function readStub(string $stubName): string
    {
        $path = self::stubsDirectory().'/'.$stubName;
        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException("Failed to read background tasks migration stub: {$path}");
        }

        return $content;
    }
}
