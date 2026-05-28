<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\MigrationsCreateCommand;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationsCreateCommandTest extends TestCase
{
    private MigrationsCreateCommand $command;

    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    protected function setUp(): void
    {
        vfsStream::setup('project', null, [
            'migrations' => [],
        ]);

        $stdout = fopen('php://memory', 'r+');
        $stderr = fopen('php://memory', 'r+');
        \assert(\is_resource($stdout));
        \assert(\is_resource($stderr));
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $output = new ConsoleOutput($this->stdout, $this->stderr);
        $this->command = new MigrationsCreateCommand($output);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
        if (is_resource($this->stderr)) {
            fclose($this->stderr);
        }
    }

    public function testExecuteCreatesMigrationFolder(): void
    {
        $migrationsPath = vfsStream::url('project/migrations');

        $exitCode = $this->command->execute(['--path='.$migrationsPath]);

        $this->assertSame(0, $exitCode);

        $folders = array_diff(scandir($migrationsPath) ?: [], ['.', '..']);
        $this->assertCount(1, $folders);
    }

    public function testExecuteCreatesSqlFiles(): void
    {
        $migrationsPath = vfsStream::url('project/migrations');

        $this->command->execute(['--path='.$migrationsPath]);

        $folders = array_diff(scandir($migrationsPath) ?: [], ['.', '..']);
        $migrationDir = $migrationsPath.'/'.reset($folders);

        $this->assertTrue(is_file($migrationDir.'/0001_migration.sql'));
        $this->assertTrue(is_file($migrationDir.'/0001_migration.rollback.sql'));
    }

    public function testMigrationFileContainsTemplate(): void
    {
        $migrationsPath = vfsStream::url('project/migrations');

        $this->command->execute(['--path='.$migrationsPath]);

        $folders = array_diff(scandir($migrationsPath) ?: [], ['.', '..']);
        $migrationDir = $migrationsPath.'/'.reset($folders);

        $content = file_get_contents($migrationDir.'/0001_migration.sql');
        \assert(\is_string($content));
        $this->assertStringContainsString('-- Migration file', $content);
    }

    public function testRollbackFileContainsTemplate(): void
    {
        $migrationsPath = vfsStream::url('project/migrations');

        $this->command->execute(['--path='.$migrationsPath]);

        $folders = array_diff(scandir($migrationsPath) ?: [], ['.', '..']);
        $migrationDir = $migrationsPath.'/'.reset($folders);

        $content = file_get_contents($migrationDir.'/0001_migration.rollback.sql');
        \assert(\is_string($content));
        $this->assertStringContainsString('-- Rollback file', $content);
    }

    public function testExecuteFailsWhenPathNotProvided(): void
    {
        $exitCode = $this->command->execute([]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenDirectoryDoesNotExist(): void
    {
        $exitCode = $this->command->execute(['--path='.vfsStream::url('project/nonexistent')]);

        $this->assertSame(1, $exitCode);
    }

    public function testHelpFlagReturnsZero(): void
    {
        $exitCode = $this->command->execute(['--help']);

        $this->assertSame(0, $exitCode);
    }

    public function testShortHelpFlagReturnsZero(): void
    {
        $exitCode = $this->command->execute(['-h']);

        $this->assertSame(0, $exitCode);
    }

    public function testOutputContainsSuccessMessage(): void
    {
        $migrationsPath = vfsStream::url('project/migrations');

        $this->command->execute(['--path='.$migrationsPath]);

        rewind($this->stdout);
        $output = stream_get_contents($this->stdout);
        \assert(\is_string($output));
        $this->assertStringContainsString('Migration created successfully', $output);
    }
}
