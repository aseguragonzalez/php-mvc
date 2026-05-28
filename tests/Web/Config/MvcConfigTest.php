<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Config;

use PhpMvc\Config\MvcConfig;
use PhpMvc\UiAssetsSettings;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MvcConfigTest extends TestCase
{
    public function testLoadReturnsDefaultsWhenConfigFileIsMissing(): void
    {
        vfsStream::setup('project', null, [
            // no mvc.config.json
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame('./assets/scripts', $config->jsAssetsPath);
        $this->assertSame('main.min.js', $config->mainJsBundler);
        $this->assertSame('./assets/styles', $config->cssAssetsPath);
        $this->assertSame('main.min.css', $config->mainCssBundler);
        $this->assertSame('./assets/i18n', $config->i18nPath);
        $this->assertSame('', $config->migrationsFolderPath);
        $this->assertNull($config->migrationsEnabled);
        $this->assertTrue($config->isMigrationsEnabled());
        $this->assertSame('Migrations', $config->effectiveMigrationsModuleRelativePath());
        $this->assertSame('', $config->backgroundTasksFolderPath);
        $this->assertNull($config->backgroundTasksEnabled);
        $this->assertFalse($config->isBackgroundTasksEnabled());
        $this->assertSame(0, $config->backgroundTasksPollIntervalSeconds);
        $this->assertSame(0, $config->effectiveBackgroundTasksPollIntervalSeconds());
        $this->assertNull($config->authenticationEnabled);
        $this->assertFalse($config->isAuthenticationEnabled());
        $this->assertSame([], $config->assetRoutes);
        $this->assertSame('main.js', $config->devMainJsBundler);
        $this->assertSame('main.css', $config->devMainCssBundler);
        $this->assertFalse($config->useDevAssets);
    }

    public function testNormalizationRemovesDotSlashAndTrailingSlashes(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts/',
                'mainJsBundler' => '/main.min.js',
                'cssAssetsPath' => 'assets/styles/',
                'mainCssBundler' => '/main.min.css',
                'i18nPath' => './assets/i18n',
                'migrationsFolderPath' => '',
                'migrationsEnabled' => false,
                'backgroundTasksFolderPath' => './BackgroundTasks/',
                'backgroundTasksEnabled' => true,
                'backgroundTasksPollIntervalSeconds' => 120,
                'authenticationEnabled' => true,
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame('assets/i18n/', $config->normalizedI18nAssetsPathForLanguageSettings());
        $this->assertSame('', $config->normalizedMigrationsFolderPath());
        $this->assertFalse($config->isMigrationsEnabled());
        $this->assertSame('Migrations', $config->effectiveMigrationsModuleRelativePath());
        $this->assertSame('BackgroundTasks', $config->normalizedBackgroundTasksFolderPath());
        $this->assertTrue($config->isBackgroundTasksEnabled());
        $this->assertSame(120, $config->backgroundTasksPollIntervalSeconds);
        $this->assertSame(120, $config->effectiveBackgroundTasksPollIntervalSeconds());
        $this->assertTrue($config->authenticationEnabled);
        $this->assertTrue($config->isAuthenticationEnabled());

        $uiAssets = UiAssetsSettings::fromConfig($config);
        $this->assertSame('/assets/scripts', $uiAssets->jsAssetsPathUrl);
        $this->assertSame('main.min.js', $uiAssets->mainJsBundler);
        $this->assertSame('/assets/styles', $uiAssets->cssAssetsPathUrl);
        $this->assertSame('main.min.css', $uiAssets->mainCssBundler);
    }

    public function testLoadParsesAssetRoutesAndUseDevAssets(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts',
                'mainJsBundler' => 'app.min.js',
                'cssAssetsPath' => './assets/styles',
                'mainCssBundler' => 'app.min.css',
                'devMainJsBundler' => 'app.js',
                'devMainCssBundler' => 'app.css',
                'useDevAssets' => true,
                'assetRoutes' => [
                    [
                        'label' => 'a',
                        'js' => ['./assets/scripts/a.js', 'assets/scripts/b.js'],
                        'css' => ['assets/styles/x.css'],
                    ],
                    [
                        'label' => 'b',
                        'js' => ['assets/scripts/a.js'],
                        'css' => [],
                    ],
                ],
                'i18nPath' => './assets/i18n',
                'migrationsFolderPath' => '',
                'migrationsEnabled' => null,
                'backgroundTasksFolderPath' => '',
                'authenticationEnabled' => null,
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertTrue($config->useDevAssets);
        $this->assertCount(2, $config->assetRoutes);
        $this->assertSame('a', $config->assetRoutes[0]->label);
        $this->assertSame(['./assets/scripts/a.js', 'assets/scripts/b.js'], $config->assetRoutes[0]->js);
        $this->assertSame(['assets/styles/x.css'], $config->assetRoutes[0]->css);
        $this->assertSame(['assets/scripts/a.js'], $config->assetRoutes[1]->js);

        $ui = UiAssetsSettings::fromConfig($config);
        $this->assertSame('app.js', $ui->mainJsBundler);
        $this->assertSame('app.css', $ui->mainCssBundler);
    }

    public function testWriteMergedToAppPersistsAuthenticationEnabled(): void
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
                'authenticationEnabled' => false,
            ], JSON_THROW_ON_ERROR),
        ]);

        $base = vfsStream::url('app');
        MvcConfig::writeMergedToApp($base, ['authenticationEnabled' => true]);

        $config = MvcConfig::load($base);
        $this->assertTrue($config->isAuthenticationEnabled());
    }

    public function testWriteMergedToAppPersistsBackgroundTasksEnabledAndPollInterval(): void
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
                'backgroundTasksFolderPath' => './BackgroundTasks',
                'backgroundTasksEnabled' => false,
                'backgroundTasksPollIntervalSeconds' => 0,
                'authenticationEnabled' => false,
            ], JSON_THROW_ON_ERROR),
        ]);

        $base = vfsStream::url('app');
        MvcConfig::writeMergedToApp($base, [
            'backgroundTasksEnabled' => true,
            'backgroundTasksPollIntervalSeconds' => 45,
        ]);

        $config = MvcConfig::load($base);
        $this->assertTrue($config->isBackgroundTasksEnabled());
        $this->assertSame(45, $config->backgroundTasksPollIntervalSeconds);
    }
}
