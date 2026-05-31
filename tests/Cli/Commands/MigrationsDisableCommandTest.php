<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\MigrationsDisableCommand;
use PhpMvc\Config\MvcConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationsDisableCommandTest extends TestCase
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
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $this->assertSame('migrations:disable', $command->getName());
    }

    public function testGetDescriptionIsNotEmpty(): void
    {
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $this->assertNotEmpty($command->getDescription());
    }

    public function testHelpFlagReturnsZero(): void
    {
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $this->assertSame(0, $command->execute(['--help']));
    }

    public function testShortHelpFlagReturnsZero(): void
    {
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $this->assertSame(0, $command->execute(['-h']));
    }

    public function testDisablesFeatureInConfig(): void
    {
        $appUrl = $this->appVfs(migrationsEnabled: true, withMigrationsDir: true);
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $exitCode = $command->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isMigrationsEnabled());
    }

    public function testRemoveFilesWithForceDeletesModuleDirectory(): void
    {
        $appUrl = $this->appVfs(migrationsEnabled: true, withMigrationsDir: true);
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $moduleDir = $appUrl.'/Migrations';
        $this->assertDirectoryExists($moduleDir);

        $exitCode = $command->execute(['--path='.$appUrl, '--remove-files', '--force']);

        $this->assertSame(0, $exitCode);
        $this->assertDirectoryDoesNotExist($moduleDir);
        $this->assertFalse(MvcConfig::load($appUrl)->isMigrationsEnabled());
    }

    public function testRemoveFilesWithoutForceReturnsError(): void
    {
        $appUrl = $this->appVfs(migrationsEnabled: true, withMigrationsDir: true);
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $exitCode = $command->execute(['--path='.$appUrl, '--remove-files']);

        $this->assertSame(1, $exitCode);
        $this->assertDirectoryExists($appUrl.'/Migrations');
    }

    public function testRemoveFilesWithForceHandlesMissingModuleDir(): void
    {
        $appUrl = $this->appVfs(migrationsEnabled: true, withMigrationsDir: false);
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $exitCode = $command->execute(['--path='.$appUrl, '--remove-files', '--force']);

        $this->assertSame(0, $exitCode);
    }

    public function testFailsWhenNotAnAppDirectory(): void
    {
        vfsStream::setup('noapp', null, ['readme.txt' => 'hello']);
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $exitCode = $command->execute(['--path='.vfsStream::url('noapp')]);

        $this->assertSame(1, $exitCode);
    }

    public function testFailsWhenConfigFileMissing(): void
    {
        vfsStream::setup('noconfig', null, ['index.php' => '<?php']);
        $command = new MigrationsDisableCommand($this->consoleOutput());

        $exitCode = $command->execute(['--path='.vfsStream::url('noconfig')]);

        $this->assertSame(1, $exitCode);
    }

    private function consoleOutput(): ConsoleOutput
    {
        return new ConsoleOutput($this->stdout, $this->stderr);
    }

    private function appVfs(bool $migrationsEnabled, bool $withMigrationsDir): string
    {
        $config = [
            'migrationsFolderPath' => './Migrations',
            'migrationsEnabled' => $migrationsEnabled,
        ];

        $structure = [
            'index.php' => '<?php',
            MvcConfig::CONFIG_FILENAME => json_encode($config, JSON_THROW_ON_ERROR),
        ];

        if ($withMigrationsDir) {
            $structure['Migrations'] = [
                'migrations' => [],
                'index.php' => '<?php // migration entrypoint',
            ];
        }

        vfsStream::setup('migrapp', null, $structure);

        return vfsStream::url('migrapp');
    }
}
