<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Migrations\Infrastructure;

use AlfonsoSG\Mvc\Migrations\Infrastructure\ShellDatabaseBackupManager;
use AlfonsoSG\Mvc\Migrations\MigrationSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ShellDatabaseBackupManagerTest extends TestCase
{
    public function testCreateTestDatabaseFromBackupThrowsWhenBackupFileDoesNotExist(): void
    {
        $settings = new MigrationSettings(
            host: 'localhost',
            database: 'reservations',
            user: 'user',
            password: 'pass',
        );
        $manager = new ShellDatabaseBackupManager($settings);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('#^Backup file does not exist: /nonexistent/backup\.sql$#');

        $manager->createTestDatabaseFromBackup('/nonexistent/backup.sql');
    }
}
