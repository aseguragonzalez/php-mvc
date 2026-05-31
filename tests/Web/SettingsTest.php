<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc;

use PhpMvc\Actions\ArrayOf;
use PhpMvc\AuthSettings;
use PhpMvc\Config\AssetRouteGroup;
use PhpMvc\ErrorMapping;
use PhpMvc\ErrorSettings;
use PhpMvc\HtmlViewEngineSettings;
use PhpMvc\LanguageSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SettingsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // AuthSettings
    // -------------------------------------------------------------------------

    public function testAuthSettingsStoresSignInAndSignOutPaths(): void
    {
        $settings = new AuthSettings('/login', '/logout');

        $this->assertSame('/login', $settings->signInPath);
        $this->assertSame('/logout', $settings->signOutPath);
    }

    public function testAuthSettingsDefaultCookieName(): void
    {
        $settings = new AuthSettings('/login', '/logout');

        $this->assertSame('auth', $settings->cookieName);
    }

    public function testAuthSettingsCustomCookieName(): void
    {
        $settings = new AuthSettings('/login', '/logout', 'session_token');

        $this->assertSame('session_token', $settings->cookieName);
    }

    // -------------------------------------------------------------------------
    // ErrorMapping
    // -------------------------------------------------------------------------

    public function testErrorMappingStoresAllValues(): void
    {
        $mapping = new ErrorMapping(404, 'error-404', 'Not Found');

        $this->assertSame(404, $mapping->statusCode);
        $this->assertSame('error-404', $mapping->templateName);
        $this->assertSame('Not Found', $mapping->pageTitle);
    }

    // -------------------------------------------------------------------------
    // ErrorSettings
    // -------------------------------------------------------------------------

    public function testErrorSettingsStoresMapping(): void
    {
        $default = new ErrorMapping(500, 'error-500', 'Internal Server Error');
        $mapping = [\RuntimeException::class => new ErrorMapping(500, 'error-500', 'Error')];

        $settings = new ErrorSettings($mapping, $default);

        $this->assertSame($default, $settings->errorsMappingDefaultValue);
        $this->assertArrayHasKey(\RuntimeException::class, $settings->errorsMapping);
    }

    public function testErrorSettingsAcceptsEmptyMapping(): void
    {
        $default = new ErrorMapping(500, 'error-500', 'Error');

        $settings = new ErrorSettings([], $default);

        $this->assertSame([], $settings->errorsMapping);
        $this->assertSame($default, $settings->errorsMappingDefaultValue);
    }

    // -------------------------------------------------------------------------
    // HtmlViewEngineSettings
    // -------------------------------------------------------------------------

    public function testHtmlViewEngineSettingsBuildsPathWithDefaultViewPath(): void
    {
        $settings = new HtmlViewEngineSettings('/var/www/app');

        $this->assertSame('/var/www/app/Views/', $settings->path);
    }

    public function testHtmlViewEngineSettingsBuildsPathWithCustomViewPath(): void
    {
        $settings = new HtmlViewEngineSettings('/var/www/app', 'Templates/');

        $this->assertSame('/var/www/app/Templates/', $settings->path);
    }

    // -------------------------------------------------------------------------
    // LanguageSettings
    // -------------------------------------------------------------------------

    public function testLanguageSettingsBuildsI18nPath(): void
    {
        $settings = new LanguageSettings('/var/www/app');

        $this->assertSame('/var/www/app/assets/i18n/', $settings->i18nPath);
    }

    public function testLanguageSettingsCustomAssetsPath(): void
    {
        $settings = new LanguageSettings('/var/www/app', 'locales/');

        $this->assertSame('/var/www/app/locales/', $settings->i18nPath);
    }

    public function testLanguageSettingsDefaultValues(): void
    {
        $settings = new LanguageSettings('/var/www/app');

        $this->assertSame(['en'], $settings->languages);
        $this->assertSame('lang', $settings->cookieName);
        $this->assertSame('en', $settings->defaultValue);
        $this->assertSame('/set-language', $settings->setUrl);
    }

    public function testLanguageSettingsCustomValues(): void
    {
        $settings = new LanguageSettings(
            basePath: '/app',
            assetsPath: 'i18n/',
            languages: ['en', 'es', 'fr'],
            cookieName: 'locale',
            defaultValue: 'es',
            setUrl: '/change-language',
        );

        $this->assertSame(['en', 'es', 'fr'], $settings->languages);
        $this->assertSame('locale', $settings->cookieName);
        $this->assertSame('es', $settings->defaultValue);
        $this->assertSame('/change-language', $settings->setUrl);
        $this->assertSame('/app/i18n/', $settings->i18nPath);
    }

    // -------------------------------------------------------------------------
    // AssetRouteGroup
    // -------------------------------------------------------------------------

    public function testAssetRouteGroupStoresAllValues(): void
    {
        $group = new AssetRouteGroup(
            label: 'main',
            js: ['assets/app.js', 'assets/lib.js'],
            css: ['assets/app.css'],
        );

        $this->assertSame('main', $group->label);
        $this->assertSame(['assets/app.js', 'assets/lib.js'], $group->js);
        $this->assertSame(['assets/app.css'], $group->css);
    }

    public function testAssetRouteGroupAcceptsEmptySourceLists(): void
    {
        $group = new AssetRouteGroup(label: 'empty', js: [], css: []);

        $this->assertSame([], $group->js);
        $this->assertSame([], $group->css);
    }

    // -------------------------------------------------------------------------
    // ArrayOf
    // -------------------------------------------------------------------------

    public function testArrayOfStoresType(): void
    {
        $attr = new ArrayOf('string');

        $this->assertSame('string', $attr->type);
    }

    public function testArrayOfStoresClassType(): void
    {
        $attr = new ArrayOf(\DateTimeImmutable::class);

        $this->assertSame(\DateTimeImmutable::class, $attr->type);
    }
}
