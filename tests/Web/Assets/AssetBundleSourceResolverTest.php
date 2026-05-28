<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Assets;

use PhpMvc\Assets\AssetBundleSourceResolver;
use PhpMvc\Config\AssetRouteGroup;
use PhpMvc\Config\MvcConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AssetBundleSourceResolverTest extends TestCase
{
    public function testMergedRelativePathsDedupeAndPreserveOrder(): void
    {
        $groups = [
            new AssetRouteGroup('one', ['a.js', './b.js'], ['x.css', '/y.css']),
            new AssetRouteGroup('two', ['b.js', 'c.js'], ['y.css']),
        ];

        $this->assertSame(['a.js', 'b.js', 'c.js'], AssetBundleSourceResolver::mergedRelativeJsPaths($groups));
        $this->assertSame(['x.css', 'y.css'], AssetBundleSourceResolver::mergedRelativeCssPaths($groups));
    }

    public function testAbsolutePathsFromAppRoot(): void
    {
        $config = new MvcConfig(
            jsAssetsPath: './out/js',
            mainJsBundler: 'm.js',
            cssAssetsPath: 'out/css',
            mainCssBundler: 'm.css',
            i18nPath: './assets/i18n',
            migrationsFolderPath: '',
            migrationsEnabled: null,
            backgroundTasksFolderPath: '',
            backgroundTasksEnabled: null,
            backgroundTasksPollIntervalSeconds: 0,
            authenticationEnabled: null,
            assetRoutes: [
                new AssetRouteGroup('g', ['sub/a.js'], ['sub/b.css']),
            ],
            devMainJsBundler: 'd.js',
            devMainCssBundler: 'd.css',
            useDevAssets: false,
        );

        $resolver = new AssetBundleSourceResolver('/app/root', $config);

        $this->assertSame(['/app/root/sub/a.js'], $resolver->absoluteJsSourcePaths());
        $this->assertSame(['/app/root/sub/b.css'], $resolver->absoluteCssSourcePaths());
        $this->assertSame('/app/root/out/js', $resolver->absoluteJsOutputDir());
        $this->assertSame('/app/root/out/css', $resolver->absoluteCssOutputDir());
    }

    public function testNormalizeRelativeAssetPath(): void
    {
        $this->assertSame('a/b', AssetBundleSourceResolver::normalizeRelativeAssetPath('./a/b/'));
        $this->assertSame('', AssetBundleSourceResolver::normalizeRelativeAssetPath(''));
    }
}
