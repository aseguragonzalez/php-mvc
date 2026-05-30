<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Config;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Config\AssetRouteGroup;
use PhpMvc\Config\MvcConfig;
use PhpMvc\UiAssetsSettings;
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

    public function testLoadThrowsRuntimeExceptionForInvalidJson(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => '{invalid: json}',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to decode JSON config/');

        MvcConfig::load(vfsStream::url('project'));
    }

    public function testEffectiveMigrationsModuleRelativePathReturnsNormalizedPathWhenSet(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'migrationsFolderPath' => './MyMigrations/',
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame('MyMigrations', $config->effectiveMigrationsModuleRelativePath());
    }

    public function testWriteMergedToAppCreatesNewFileWhenNoConfigFileExists(): void
    {
        vfsStream::setup('newapp', null, []);

        $base = vfsStream::url('newapp');
        MvcConfig::writeMergedToApp($base, ['authenticationEnabled' => true]);

        $config = MvcConfig::load($base);
        $this->assertTrue($config->isAuthenticationEnabled());
        $this->assertSame('./assets/scripts', $config->jsAssetsPath);
    }

    public function testWriteMergedToAppWithInvalidJsonInExistingFileWritesDefaults(): void
    {
        vfsStream::setup('badapp', null, [
            MvcConfig::CONFIG_FILENAME => '{invalid: json}',
        ]);

        $base = vfsStream::url('badapp');
        MvcConfig::writeMergedToApp($base, ['authenticationEnabled' => true]);

        $config = MvcConfig::load($base);
        $this->assertTrue($config->isAuthenticationEnabled());
        $this->assertSame('./assets/scripts', $config->jsAssetsPath);
    }

    public function testAssetRoutesToJsonArrayConvertsRouteGroups(): void
    {
        $routes = [
            new AssetRouteGroup('admin', ['assets/scripts/admin.js'], ['assets/styles/admin.css']),
            new AssetRouteGroup('public', [], ['assets/styles/public.css']),
        ];

        $result = MvcConfig::assetRoutesToJsonArray($routes);

        $this->assertCount(2, $result);
        $this->assertSame('admin', $result[0]['label']);
        $this->assertSame(['assets/scripts/admin.js'], $result[0]['js']);
        $this->assertSame(['assets/styles/admin.css'], $result[0]['css']);
        $this->assertSame('public', $result[1]['label']);
        $this->assertSame([], $result[1]['js']);
        $this->assertSame(['assets/styles/public.css'], $result[1]['css']);
    }

    public function testLoadThrowsRuntimeExceptionWhenConfigFileCannotBeRead(): void
    {
        $root = vfsStream::setup('root');
        vfsStream::newFile(MvcConfig::CONFIG_FILENAME, 0o000)->at($root);

        set_error_handler(static fn (): bool => true);

        try {
            MvcConfig::load(vfsStream::url('root'));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Failed to read config', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    public function testWriteMergedToAppPreservesAssetRoutesFromExistingConfig(): void
    {
        vfsStream::setup('app', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'assetRoutes' => [
                    ['label' => 'main', 'js' => ['app.js'], 'css' => ['app.css']],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $base = vfsStream::url('app');
        MvcConfig::writeMergedToApp($base, []);

        $config = MvcConfig::load($base);
        $this->assertCount(1, $config->assetRoutes);
        $this->assertSame('main', $config->assetRoutes[0]->label);
    }

    public function testWriteMergedToAppThrowsWhenJsonEncodeFails(): void
    {
        vfsStream::setup('app', null, []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode mvc.config.json');

        MvcConfig::writeMergedToApp(vfsStream::url('app'), ['jsAssetsPath' => "\xB1\x31"]);
    }

    public function testNormalizedI18nAssetsPathReturnsDefaultWhenI18nPathIsEmpty(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(['i18nPath' => ''], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame('assets/i18n/', $config->normalizedI18nAssetsPathForLanguageSettings());
    }

    public function testParseAssetRoutesSkipsNonArrayItems(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'assetRoutes' => [
                    'not-an-array',
                    ['label' => 'main', 'js' => [], 'css' => []],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertCount(1, $config->assetRoutes);
        $this->assertSame('main', $config->assetRoutes[0]->label);
    }

    public function testGetStringFallsBackToDefaultForNonStringValue(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(['jsAssetsPath' => 123], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame('./assets/scripts', $config->jsAssetsPath);
    }

    public function testGetBoolFallsBackToDefaultForNonBoolValue(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(['useDevAssets' => 'yes'], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertFalse($config->useDevAssets);
    }

    public function testGetIntFallsBackToDefaultForNonIntValue(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(
                ['backgroundTasksPollIntervalSeconds' => '45'],
                JSON_THROW_ON_ERROR
            ),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame(0, $config->backgroundTasksPollIntervalSeconds);
    }

    public function testGetStringListFromMixedReturnsEmptyArrayForNonArrayValue(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'assetRoutes' => [
                    ['label' => 'main', 'js' => 'script.js', 'css' => []],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame([], $config->assetRoutes[0]->js);
    }

    public function testGetStringFromMixedFallsBackToDefaultForNonStringLabel(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'assetRoutes' => [
                    ['label' => 42, 'js' => [], 'css' => []],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));

        $this->assertSame('', $config->assetRoutes[0]->label);
    }
}
