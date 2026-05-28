<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\MvcCli;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MvcCliTest extends TestCase
{
    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    protected function setUp(): void
    {
        $stdout = fopen('php://memory', 'r+');
        $stderr = fopen('php://memory', 'r+');
        \assert(\is_resource($stdout));
        \assert(\is_resource($stderr));
        $this->stdout = $stdout;
        $this->stderr = $stderr;
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

    public function testRunWithNoArgsShowsHelpAndReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Available commands:', $output);
        $this->assertStringContainsString('create-app', $output);
        $this->assertStringContainsString('initialize-migrations', $output);
        $this->assertStringContainsString('migrations:enable', $output);
        $this->assertStringContainsString('migrations:disable', $output);
        $this->assertStringContainsString('initialize-background-tasks', $output);
        $this->assertStringContainsString('background-tasks:enable', $output);
        $this->assertStringContainsString('background-tasks:disable', $output);
        $this->assertStringContainsString('background-tasks:run', $output);
        $this->assertStringContainsString('migrations:create', $output);
        $this->assertStringContainsString('migrations:run', $output);
        $this->assertStringContainsString('migrations:test', $output);
        $this->assertStringContainsString('auth:enable', $output);
        $this->assertStringContainsString('auth:disable', $output);
        $this->assertStringContainsString('watch-assets', $output);
        $this->assertStringContainsString('create-bundle', $output);
    }

    public function testRunWithHelpFlagShowsHelp(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function testRunWithUnknownCommandReturnsOne(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'unknown-command']);

        $this->assertSame(1, $exitCode);
    }

    public function testRunWithCreateAppHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'create-app', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--name=', $output);
        $this->assertStringContainsString('--namespace=', $output);
    }

    public function testRunWithInitializeMigrationsHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'initialize-migrations', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--folder=', $output);
    }

    public function testRunWithMigrationsEnableHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'migrations:enable', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--folder=', $output);
    }

    public function testRunWithMigrationsDisableHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'migrations:disable', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--remove-files', $output);
        $this->assertStringContainsString('--force', $output);
    }

    public function testRunWithInitializeBackgroundTasksHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'initialize-background-tasks', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--name=', $output);
        $this->assertStringContainsString('--namespace=', $output);
    }

    public function testRunWithMigrationsCreateHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'migrations:create', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--app-path=', $output);
    }

    public function testRunWithMigrationsRunHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'migrations:run', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--app-path=', $output);
    }

    public function testRunWithMigrationsTestHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'migrations:test', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--app-path=', $output);
        $this->assertStringContainsString('--migration=', $output);
    }

    public function testRunWithAuthEnableHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'auth:enable', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--skip-migrations', $output);
    }

    public function testRunWithAuthDisableHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'auth:disable', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--path=', $output);
        $this->assertStringContainsString('--skip-migrations', $output);
    }

    public function testRunWithBackgroundTasksRunHelpReturnsZero(): void
    {
        $cli = $this->createCli();

        $exitCode = $cli->run(['mvc', 'background-tasks:run', '--help']);

        $output = $this->readStream($this->stdout);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('--app-path=', $output);
        $this->assertStringContainsString('--force', $output);
    }

    private function createCli(): MvcCli
    {
        $output = new ConsoleOutput($this->stdout, $this->stderr);

        return new MvcCli($output);
    }

    /**
     * @param resource $stream
     */
    private function readStream(mixed $stream): string
    {
        rewind($stream);
        $content = stream_get_contents($stream);

        return false !== $content ? $content : '';
    }
}
