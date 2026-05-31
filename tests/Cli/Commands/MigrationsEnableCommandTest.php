<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\MigrationsEnableCommand;
use PhpMvc\Commands\StubGenerator;
use PhpMvc\Config\MvcConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationsEnableCommandTest extends TestCase
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

    public function testGetNameReturnsCommandName(): void
    {
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $this->assertSame('migrations:enable', $command->getName());
    }

    public function testGetDescriptionIsNotEmpty(): void
    {
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $this->assertNotEmpty($command->getDescription());
    }

    public function testHelpFlagReturnsZero(): void
    {
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $this->assertSame(0, $command->execute(['--help']));
    }

    public function testShortHelpFlagReturnsZero(): void
    {
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $this->assertSame(0, $command->execute(['-h']));
    }

    public function testFailsWhenNotAnAppDirectory(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
        ]);
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $exitCode = $command->execute(['--path='.vfsStream::url('project')]);

        $this->assertSame(1, $exitCode);
    }

    public function testFailsWhenFolderNameContainsDotDot(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'app' => ['index.php' => '<?php'],
        ]);
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $exitCode = $command->execute([
            '--path='.vfsStream::url('project/app'),
            '--folder=../escape',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testFailsWhenModuleDirAlreadyExists(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'app' => [
                'index.php' => '<?php',
                'Migrations' => [],
            ],
        ]);
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());

        $exitCode = $command->execute(['--path='.vfsStream::url('project/app')]);

        $this->assertSame(1, $exitCode);
    }

    public function testEnablesWithCustomFolderName(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'app' => ['index.php' => '<?php'],
        ]);
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());
        $appUrl = vfsStream::url('project/app');

        $exitCode = $command->execute([
            '--path='.$appUrl,
            '--folder=CustomMigrations',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDirectoryExists($appUrl.'/CustomMigrations');
        $this->assertDirectoryExists($appUrl.'/CustomMigrations/migrations');
        $this->assertFileExists($appUrl.'/CustomMigrations/index.php');

        $config = MvcConfig::load($appUrl);
        $this->assertTrue($config->isMigrationsEnabled());
    }

    public function testEmptyFolderArgumentNormalizesToDefault(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'app' => ['index.php' => '<?php'],
        ]);
        $command = new MigrationsEnableCommand($this->consoleOutput(), new StubGenerator());
        $appUrl = vfsStream::url('project/app');

        $exitCode = $command->execute([
            '--path='.$appUrl,
            '--folder=',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDirectoryExists($appUrl.'/Migrations');
    }

    private function consoleOutput(): ConsoleOutput
    {
        return new ConsoleOutput($this->stdout, $this->stderr);
    }
}
