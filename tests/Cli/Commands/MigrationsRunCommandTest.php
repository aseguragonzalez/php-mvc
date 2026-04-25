<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Commands\ConsoleOutput;
use AlfonsoSG\Mvc\Commands\MigrationsRunCommand;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationsRunCommandTest extends TestCase
{
    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    private ConsoleOutput $consoleOutput;

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
        $this->consoleOutput = new ConsoleOutput($this->stdout, $this->stderr);
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

    public function testExecuteDelegatesToMigrationRunner(): void
    {
        $capturedBasePath = null;
        $capturedArgv = null;
        $runner = function (string $basePath, array $argv) use (&$capturedBasePath, &$capturedArgv): int {
            $capturedBasePath = $basePath;
            $capturedArgv = $argv;

            return 0;
        };
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);
        $migrationsPath = vfsStream::url('project/migrations');

        $exitCode = $command->execute(['--path='.$migrationsPath]);

        $this->assertSame(0, $exitCode);
        $this->assertSame($migrationsPath, $capturedBasePath);
        $this->assertSame([], $capturedArgv);
    }

    public function testExecuteReturnsRunnerExitCode(): void
    {
        $runner = fn (string $basePath, array $argv): int => 1;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);
        $migrationsPath = vfsStream::url('project/migrations');

        $exitCode = $command->execute(['--path='.$migrationsPath]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenPathNotProvided(): void
    {
        $runner = fn (string $basePath, array $argv): int => 0;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);

        $exitCode = $command->execute([]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenDirectoryDoesNotExist(): void
    {
        $runner = fn (string $basePath, array $argv): int => 0;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);

        $exitCode = $command->execute(['--path='.vfsStream::url('project/nonexistent')]);

        $this->assertSame(1, $exitCode);
    }

    public function testHelpFlagReturnsZero(): void
    {
        $runner = fn (string $basePath, array $argv): int => 0;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);

        $exitCode = $command->execute(['--help']);

        $this->assertSame(0, $exitCode);
    }

    public function testShortHelpFlagReturnsZero(): void
    {
        $runner = fn (string $basePath, array $argv): int => 0;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);

        $exitCode = $command->execute(['-h']);

        $this->assertSame(0, $exitCode);
    }

    public function testHelpOutputContainsUsageInfo(): void
    {
        $runner = fn (string $basePath, array $argv): int => 0;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);

        $command->execute(['--help']);

        rewind($this->stdout);
        $output = stream_get_contents($this->stdout);
        \assert(\is_string($output));
        $this->assertStringContainsString('--path=', $output);
    }

    public function testOutputContainsInfoMessage(): void
    {
        $runner = fn (string $basePath, array $argv): int => 0;
        $command = new MigrationsRunCommand($this->consoleOutput, $runner);
        $migrationsPath = vfsStream::url('project/migrations');

        $command->execute(['--path='.$migrationsPath]);

        rewind($this->stdout);
        $output = stream_get_contents($this->stdout);
        \assert(\is_string($output));
        $this->assertStringContainsString('Running pending migrations', $output);
    }
}
