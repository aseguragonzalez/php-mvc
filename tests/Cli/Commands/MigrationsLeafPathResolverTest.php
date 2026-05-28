<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use PhpMvc\Commands\MigrationsLeafPathResolver;
use PhpMvc\Config\MvcConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationsLeafPathResolverTest extends TestCase
{
    public function testResolveReturnsNullWhenConfigMissing(): void
    {
        vfsStream::setup('app', null, [
            'index.php' => '<?php',
        ]);

        $this->assertNull(MigrationsLeafPathResolver::resolveLeafMigrationsDir(vfsStream::url('app')));
    }

    public function testResolveReturnsNullWhenMigrationsDisabled(): void
    {
        vfsStream::setup('app', null, [
            'index.php' => '<?php',
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts',
                'mainJsBundler' => 'main.min.js',
                'cssAssetsPath' => './assets/styles',
                'mainCssBundler' => 'main.min.css',
                'i18nPath' => './assets/i18n',
                'migrationsFolderPath' => '',
                'migrationsEnabled' => false,
                'backgroundTasksFolderPath' => '',
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertNull(MigrationsLeafPathResolver::resolveLeafMigrationsDir(vfsStream::url('app')));
    }

    public function testResolveReturnsAbsoluteLeafPathWhenEnabled(): void
    {
        $appUrl = vfsStream::url('app');
        vfsStream::setup('app', null, [
            'index.php' => '<?php',
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts',
                'mainJsBundler' => 'main.min.js',
                'cssAssetsPath' => './assets/styles',
                'mainCssBundler' => 'main.min.css',
                'i18nPath' => './assets/i18n',
                'migrationsFolderPath' => './Migrations',
                'migrationsEnabled' => true,
                'backgroundTasksFolderPath' => '',
            ], JSON_THROW_ON_ERROR),
            'Migrations' => [
                'migrations' => [],
            ],
        ]);

        $expected = $appUrl.'/Migrations/migrations';
        $this->assertSame($expected, MigrationsLeafPathResolver::resolveLeafMigrationsDir($appUrl));
    }
}
