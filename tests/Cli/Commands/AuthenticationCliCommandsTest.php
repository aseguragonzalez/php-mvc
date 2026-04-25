<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Commands\AuthenticationDisableCommand;
use AlfonsoSG\Mvc\Commands\AuthenticationEnableCommand;
use AlfonsoSG\Mvc\Commands\ConsoleOutput;
use AlfonsoSG\Mvc\Config\MvcConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuthenticationCliCommandsTest extends TestCase
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

    public function testAuthEnableCreatesMigrationAndSetsFlag(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: false, withMigrationsModule: true);
        $enable = new AuthenticationEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);

        $loaded = MvcConfig::load($appUrl);
        $this->assertTrue($loaded->isAuthenticationEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(1, $folders);
        $dir = $leaf.'/'.reset($folders);
        $up = file_get_contents($dir.'/0001_migration.sql');
        \assert(\is_string($up));
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS users', $up);
        $down = file_get_contents($dir.'/0001_migration.rollback.sql');
        \assert(\is_string($down));
        $this->assertStringContainsString('DROP TABLE IF EXISTS users', $down);
    }

    public function testAuthEnableIdempotentWhenAlreadyEnabled(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: true, withMigrationsModule: true);
        $enable = new AuthenticationEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testAuthEnableSkipMigrationsOnlyUpdatesConfig(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: false, withMigrationsModule: true);
        $enable = new AuthenticationEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl, '--skip-migrations']);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(MvcConfig::load($appUrl)->isAuthenticationEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testAuthEnableFailsWhenMigrationsUnavailable(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: false, withMigrationsModule: false);
        $enable = new AuthenticationEnableCommand($this->consoleOutput());

        $exitCode = $enable->execute(['--path='.$appUrl]);

        $this->assertSame(1, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isAuthenticationEnabled());
    }

    public function testAuthDisableCreatesTeardownMigrationAndClearsFlag(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: true, withMigrationsModule: true);
        $disable = new AuthenticationDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isAuthenticationEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(1, $folders);
        $dir = $leaf.'/'.reset($folders);
        $up = file_get_contents($dir.'/0001_migration.sql');
        \assert(\is_string($up));
        $this->assertStringContainsString('DROP TABLE IF EXISTS users', $up);
        $rollback = file_get_contents($dir.'/0001_migration.rollback.sql');
        \assert(\is_string($rollback));
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS users', $rollback);
    }

    public function testAuthDisableIdempotentWhenAlreadyDisabled(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: false, withMigrationsModule: true);
        $disable = new AuthenticationDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl]);

        $this->assertSame(0, $exitCode);

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testAuthDisableSkipMigrationsOnlyUpdatesConfig(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: true, withMigrationsModule: true);
        $disable = new AuthenticationDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl, '--skip-migrations']);

        $this->assertSame(0, $exitCode);
        $this->assertFalse(MvcConfig::load($appUrl)->isAuthenticationEnabled());

        $leaf = $appUrl.'/Migrations/migrations';
        $folders = array_diff(scandir($leaf) ?: [], ['.', '..']);
        $this->assertCount(0, $folders);
    }

    public function testAuthDisableFailsWhenMigrationsUnavailable(): void
    {
        $appUrl = $this->appVfs(authenticationEnabled: true, withMigrationsModule: false);
        $disable = new AuthenticationDisableCommand($this->consoleOutput());

        $exitCode = $disable->execute(['--path='.$appUrl]);

        $this->assertSame(1, $exitCode);
        $this->assertTrue(MvcConfig::load($appUrl)->isAuthenticationEnabled());
    }

    private function consoleOutput(): ConsoleOutput
    {
        return new ConsoleOutput($this->stdout, $this->stderr);
    }

    private function appVfs(bool $authenticationEnabled, bool $withMigrationsModule): string
    {
        $config = [
            'jsAssetsPath' => './assets/scripts',
            'mainJsBundler' => 'main.min.js',
            'cssAssetsPath' => './assets/styles',
            'mainCssBundler' => 'main.min.css',
            'i18nPath' => './assets/i18n',
            'migrationsFolderPath' => './Migrations',
            'migrationsEnabled' => true,
            'backgroundTasksFolderPath' => '',
            'authenticationEnabled' => $authenticationEnabled,
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

        vfsStream::setup('authapp', null, $structure);

        return vfsStream::url('authapp');
    }
}
