<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\InitializeMigrationsCommand;
use PhpMvc\Commands\MigrationsEnableCommand;
use PhpMvc\Commands\StubGenerator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InitializeMigrationsCommandTest extends TestCase
{
    private InitializeMigrationsCommand $command;

    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    protected function setUp(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'vendor' => [
                'autoload.php' => '<?php // autoload',
            ],
            'src' => [
                'Ports' => [
                    'MyApp' => [
                        'index.php' => '<?php // app entry point',
                    ],
                ],
            ],
        ]);

        $stdout = fopen('php://memory', 'r+');
        $stderr = fopen('php://memory', 'r+');
        \assert(\is_resource($stdout));
        \assert(\is_resource($stderr));
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $output = new ConsoleOutput($this->stdout, $this->stderr);
        $stubGenerator = new StubGenerator();
        $this->command = new InitializeMigrationsCommand(new MigrationsEnableCommand($output, $stubGenerator));
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

    public function testExecuteCreatesMigrationsStructure(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute(['--path='.$appPath]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(is_dir($appPath.'/Migrations'));
        $this->assertTrue(is_dir($appPath.'/Migrations/migrations'));
        $this->assertTrue(is_file($appPath.'/Migrations/index.php'));

        $configContent = file_get_contents($appPath.'/mvc.config.json');
        \assert(\is_string($configContent));

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('./Migrations', $decoded['migrationsFolderPath']);
        $this->assertTrue($decoded['migrationsEnabled']);
    }

    public function testGeneratedIndexPhpContainsMigrationApp(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute(['--path='.$appPath]);

        $content = file_get_contents($appPath.'/Migrations/index.php');
        \assert(\is_string($content));
        $this->assertStringContainsString('MigrationApp', $content);
        $this->assertStringContainsString('MigrationBootstrap', $content);
        $this->assertStringContainsString("'/migrations'", $content);
    }

    public function testGeneratedIndexPhpContainsCorrectAutoloadPath(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute(['--path='.$appPath]);

        $content = file_get_contents($appPath.'/Migrations/index.php');
        \assert(\is_string($content));
        $this->assertStringContainsString("'/../../../../vendor/autoload.php'", $content);
    }

    public function testExecuteFailsWhenNotAnAppDirectory(): void
    {
        $nonAppPath = vfsStream::url('project/src/Ports');

        $exitCode = $this->command->execute(['--path='.$nonAppPath]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenMigrationsAlreadyExists(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');
        mkdir($appPath.'/Migrations', 0o755, true);

        $exitCode = $this->command->execute(['--path='.$appPath]);

        $this->assertSame(1, $exitCode);
    }

    public function testHelpFlagReturnsZero(): void
    {
        $exitCode = $this->command->execute(['--help']);

        $this->assertSame(0, $exitCode);
    }
}
