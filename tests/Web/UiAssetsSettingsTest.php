<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Config\MvcConfig;
use PhpMvc\UiAssetsSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UiAssetsSettingsTest extends TestCase
{
    public function testFromConfigNormalizesAssetsPath(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts',
                'cssAssetsPath' => './assets/styles',
                'mainJsBundler' => '/main.min.js',
                'mainCssBundler' => '/main.min.css',
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));
        $settings = UiAssetsSettings::fromConfig($config);

        $this->assertSame('/assets/scripts', $settings->jsAssetsPathUrl);
        $this->assertSame('/assets/styles', $settings->cssAssetsPathUrl);
        $this->assertSame('main.min.js', $settings->mainJsBundler);
        $this->assertSame('main.min.css', $settings->mainCssBundler);
    }

    public function testFromConfigWithEmptyAssetsPathReturnsRootUrl(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => '',
                'cssAssetsPath' => '',
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));
        $settings = UiAssetsSettings::fromConfig($config);

        $this->assertSame('/', $settings->jsAssetsPathUrl);
        $this->assertSame('/', $settings->cssAssetsPathUrl);
    }

    public function testFromConfigUsesDevBundlersWhenUseDevAssetsIsTrue(): void
    {
        vfsStream::setup('project', null, [
            MvcConfig::CONFIG_FILENAME => json_encode([
                'useDevAssets' => true,
                'devMainJsBundler' => 'app.js',
                'devMainCssBundler' => 'app.css',
                'mainJsBundler' => 'app.min.js',
                'mainCssBundler' => 'app.min.css',
            ], JSON_THROW_ON_ERROR),
        ]);

        $config = MvcConfig::load(vfsStream::url('project'));
        $settings = UiAssetsSettings::fromConfig($config);

        $this->assertSame('app.js', $settings->mainJsBundler);
        $this->assertSame('app.css', $settings->mainCssBundler);
    }
}
