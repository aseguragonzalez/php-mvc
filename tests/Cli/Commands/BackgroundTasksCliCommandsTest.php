<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Commands\BackgroundTasksDisableCommand;
use PhpMvc\Commands\BackgroundTasksEnableCommand;
use PhpMvc\Commands\BackgroundTasksRunCommand;
use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Config\MvcConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BackgroundTasksCliCommandsTest extends TestCase
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

    public function testBackgroundTasksEnableCreatesMigrationAndSetsFlag(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: true,
            withBgIndex: true,
        );
        $enable = new BackgroundTasksEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);

        $loaded = MvcConfig::load($appUrl);
        $this->assertTrue($loaded->isBackgroundTasksEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(1, $folders);
        $dir = $leaf.'/'.reset($folders);
        $up = file_get_contents($dir.'/0001_migration.sql');
        \assert(\is_string($up));
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `background_tasks`', $up);
        $down = file_get_contents($dir.'/0001_migration.rollback.sql');
        \assert(\is_string($down));
        $this->assertStringContainsString('DROP TABLE IF EXISTS `background_tasks`', $down);
    }

    public function testBackgroundTasksEnableIdempotentWhenAlreadyEnabled(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: true,
            withMigrationsModule: true,
            withBgIndex: true,
        );
        $enable = new BackgroundTasksEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testBackgroundTasksEnableSkipMigrationsOnlyUpdatesConfig(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: true,
            withBgIndex: true,
        );
        $enable = new BackgroundTasksEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl, '--skip-migrations']);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(MvcConfig::load($appUrl)->isBackgroundTasksEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testBackgroundTasksEnableFailsWhenEntryMissing(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: true,
            withBgIndex: false,
        );
        $enable = new BackgroundTasksEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(1, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isBackgroundTasksEnabled());
    }

    public function testBackgroundTasksEnableFailsWhenMigrationsUnavailable(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: false,
            withBgIndex: true,
        );
        $enable = new BackgroundTasksEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(1, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isBackgroundTasksEnabled());
    }

    public function testBackgroundTasksDisableIdempotentWhenAlreadyDisabled(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: true,
            withBgIndex: true,
        );
        $disable = new BackgroundTasksDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testBackgroundTasksDisableCreatesTeardownMigrationAndClearsFlag(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: true,
            withMigrationsModule: true,
            withBgIndex: true,
        );
        $disable = new BackgroundTasksDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isBackgroundTasksEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(1, $folders);
        $dir = $leaf.'/'.reset($folders);
        $up = file_get_contents($dir.'/0001_migration.sql');
        \assert(\is_string($up));
        $this->assertStringContainsString('DROP TABLE IF EXISTS `background_tasks`', $up);
        $rollback = file_get_contents($dir.'/0001_migration.rollback.sql');
        \assert(\is_string($rollback));
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `background_tasks`', $rollback);
    }

    public function testBackgroundTasksRunInvokesRunnerWithResolvedIndex(): void
    {
        [$appUrl, $expectedIndex] = $this->appVfs(
            backgroundTasksEnabled: true,
            withMigrationsModule: true,
            withBgIndex: true,
        );

        $capturedIndex = null;
        $capturedForward = null;
        $run = new BackgroundTasksRunCommand(
            $this->consoleOutput(),
            function (
                string $indexPath,
                array $forwardArgs,
            ) use (
                &$capturedIndex,
                &$capturedForward,
                $expectedIndex,
            ): int {
                $capturedIndex = $indexPath;
                $capturedForward = $forwardArgs;

                return $indexPath === $expectedIndex ? 0 : 1;
            },
        );

        $exitCode = $run->execute(['--app-path='.$appUrl, '--', '--interval=5']);

        $this->assertSame(0, $exitCode);
        $this->assertSame($expectedIndex, $capturedIndex);
        $this->assertSame(['--interval=5'], $capturedForward);
    }

    public function testBackgroundTasksRunFailsWhenDisabledWithoutForce(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: true,
            withBgIndex: true,
        );

        $invoked = false;
        $run = new BackgroundTasksRunCommand($this->consoleOutput(), function () use (&$invoked): int {
            $invoked = true;

            return 0;
        });

        $exitCode = $run->execute(['--app-path='.$appUrl]);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($invoked);
    }

    public function testBackgroundTasksDisableSkipMigrationsOnlyUpdatesConfig(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: true,
            withMigrationsModule: true,
            withBgIndex: true,
        );
        $disable = new BackgroundTasksDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl, '--skip-migrations']);

        $this->assertSame(0, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isBackgroundTasksEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testBackgroundTasksRunWithForceWhenDisabled(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: false,
            withMigrationsModule: true,
            withBgIndex: true,
        );

        $invoked = false;
        $run = new BackgroundTasksRunCommand($this->consoleOutput(), function () use (&$invoked): int {
            $invoked = true;

            return 0;
        });

        $exitCode = $run->execute(['--app-path='.$appUrl, '--force']);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($invoked);
    }

    public function testBackgroundTasksDisableFailsWhenMigrationsUnavailable(): void
    {
        [$appUrl] = $this->appVfs(
            backgroundTasksEnabled: true,
            withMigrationsModule: false,
            withBgIndex: true,
        );
        $disable = new BackgroundTasksDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl]);

        $this->assertSame(1, $exitCode);
        $this->assertTrue(MvcConfig::load($appUrl)->isBackgroundTasksEnabled());
    }

    private function consoleOutput(): ConsoleOutput
    {
        return new ConsoleOutput($this->stdout, $this->stderr);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function appVfs(bool $backgroundTasksEnabled, bool $withMigrationsModule, bool $withBgIndex): array
    {
        $config = [
            'jsAssetsPath' => './assets/scripts',
            'mainJsBundler' => 'main.min.js',
            'cssAssetsPath' => './assets/styles',
            'mainCssBundler' => 'main.min.css',
            'i18nPath' => './assets/i18n',
            'migrationsFolderPath' => './Migrations',
            'migrationsEnabled' => true,
            'backgroundTasksFolderPath' => './BackgroundTasks',
            'backgroundTasksEnabled' => $backgroundTasksEnabled,
            'backgroundTasksPollIntervalSeconds' => 0,
            'authenticationEnabled' => false,
        ];

        $structure = [
            'index.php' => '<?php',
            MvcConfig::CONFIG_FILENAME => json_encode($config, JSON_THROW_ON_ERROR),
        ];

        if ($withMigrationsModule) {
            $structure['Migrations'] = [
                'migrations' => [],
            ];
        }

        if ($withBgIndex) {
            $structure['BackgroundTasks'] = [
                'index.php' => '<?php',
            ];
        }

        vfsStream::setup('bgapp', null, $structure);

        return [vfsStream::url('bgapp'), vfsStream::url('bgapp').'/BackgroundTasks/index.php'];
    }
}
