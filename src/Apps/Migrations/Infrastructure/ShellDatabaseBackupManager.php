<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Infrastructure;

use AlfonsoSG\Mvc\Migrations\Domain\Services\DatabaseBackupManager;
use AlfonsoSG\Mvc\Migrations\MigrationSettings;

/** @note Requires MySQL/MariaDB CLI tools (`mysqldump`, `mysql`) on the server PATH. Not compatible with PostgreSQL or SQLite. */
final readonly class ShellDatabaseBackupManager implements DatabaseBackupManager
{
    public function __construct(private MigrationSettings $settings) {}

    public function backup(): string
    {
        $backupFile = sys_get_temp_dir().'/migration_test_'.uniqid().'.sql';
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s --no-data %s > %s 2>&1',
            escapeshellarg($this->settings->host),
            escapeshellarg($this->settings->user),
            escapeshellarg($this->settings->password),
            escapeshellarg($this->settings->database),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);

        if (0 !== $returnCode) {
            throw new \RuntimeException('Failed to create database backup: '.implode("\n", $output));
        }

        return $backupFile;
    }

    public function restore(string $backupFilePath): void
    {
        if (!file_exists($backupFilePath)) {
            throw new \RuntimeException("Backup file does not exist: {$backupFilePath}");
        }

        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($this->settings->host),
            escapeshellarg($this->settings->user),
            escapeshellarg($this->settings->password),
            escapeshellarg($this->settings->database),
            escapeshellarg($backupFilePath)
        );

        exec($command, $output, $returnCode);

        if (0 !== $returnCode) {
            throw new \RuntimeException('Failed to restore database from backup: '.implode("\n", $output));
        }

        unlink($backupFilePath);
    }

    public function createTestDatabaseFromBackup(string $backupFilePath): string
    {
        if (!file_exists($backupFilePath)) {
            throw new \RuntimeException("Backup file does not exist: {$backupFilePath}");
        }

        $testDatabaseName = 'test_'.bin2hex(random_bytes(4));

        $createDbCommand = sprintf(
            'mysql -h %s -u %s -p%s -e "CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" 2>&1',
            escapeshellarg($this->settings->host),
            escapeshellarg($this->settings->user),
            escapeshellarg($this->settings->password),
            $this->escapeDatabaseName($testDatabaseName)
        );

        exec($createDbCommand, $output, $returnCode);
        if (0 !== $returnCode) {
            throw new \RuntimeException(
                'Failed to create test database: '.implode("\n", $output)
            );
        }

        $restoreCommand = sprintf(
            'mysql -h %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($this->settings->host),
            escapeshellarg($this->settings->user),
            escapeshellarg($this->settings->password),
            escapeshellarg($testDatabaseName),
            escapeshellarg($backupFilePath)
        );

        exec($restoreCommand, $restoreOutput, $restoreReturnCode);
        if (0 !== $restoreReturnCode) {
            $this->destroyTestDatabase($testDatabaseName);

            throw new \RuntimeException(
                'Failed to restore backup into test database: '.implode("\n", $restoreOutput)
            );
        }

        return $testDatabaseName;
    }

    public function destroyTestDatabase(string $testDatabaseName): void
    {
        $command = sprintf(
            'mysql -h %s -u %s -p%s -e "DROP DATABASE IF EXISTS %s" 2>&1',
            escapeshellarg($this->settings->host),
            escapeshellarg($this->settings->user),
            escapeshellarg($this->settings->password),
            $this->escapeDatabaseName($testDatabaseName)
        );

        exec($command, $output, $returnCode);
        if (0 !== $returnCode) {
            throw new \RuntimeException(
                'Failed to destroy test database: '.implode("\n", $output)
            );
        }
    }

    private function escapeDatabaseName(string $name): string
    {
        $escapedName = str_replace('`', '``', $name);

        return sprintf('`%s`', $escapedName);
    }
}
