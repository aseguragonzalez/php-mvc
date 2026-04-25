<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Responses\Headers;

use AlfonsoSG\Mvc\Responses\Headers\CacheControl;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CacheControlTest extends TestCase
{
    public function testDefaultCacheControl(): void
    {
        $cacheControl = new CacheControl();

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertSame('public', $cacheControl->value);
    }

    public function testNoCache(): void
    {
        $cacheControl = new CacheControl(noCache: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertSame('no-cache, public', $cacheControl->value);
    }

    public function testNoStore(): void
    {
        $cacheControl = new CacheControl(noStore: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertSame('no-store, public', $cacheControl->value);
    }

    public function testMaxAge(): void
    {
        $cacheControl = new CacheControl(maxAge: 3600);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertSame('max-age=3600, public', $cacheControl->value);
    }

    public function testSMaxAge(): void
    {
        $cacheControl = new CacheControl(sMaxAge: 3600);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertSame('s-maxage=3600, public', $cacheControl->value);
    }

    public function testPrivate(): void
    {
        $cacheControl = new CacheControl(public: false, private: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertSame('private', $cacheControl->value);
    }

    public function testMustRevalidate(): void
    {
        $cacheControl = new CacheControl(mustRevalidate: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('must-revalidate', $cacheControl->value);
    }

    public function testProxyRevalidate(): void
    {
        $cacheControl = new CacheControl(proxyRevalidate: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('proxy-revalidate', $cacheControl->value);
    }

    public function testNoTransform(): void
    {
        $cacheControl = new CacheControl(noTransform: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('no-transform', $cacheControl->value);
    }

    public function testImmutable(): void
    {
        $cacheControl = new CacheControl(immutable: true);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('immutable', $cacheControl->value);
    }

    public function testStaleWhileRevalidate(): void
    {
        $cacheControl = new CacheControl(staleWhileRevalidate: 3600);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('stale-while-revalidate=3600', $cacheControl->value);
    }

    public function testStaleIfError(): void
    {
        $cacheControl = new CacheControl(staleIfError: 3600);

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('stale-if-error=3600', $cacheControl->value);
    }

    public function testMultipleDirectives(): void
    {
        $cacheControl = new CacheControl(
            noCache: true,
            noStore: true,
            maxAge: 3600,
            sMaxAge: 7200,
            public: false,
            private: true,
            mustRevalidate: true,
            proxyRevalidate: true,
            noTransform: true,
            immutable: true,
            staleWhileRevalidate: 3600,
            staleIfError: 7200
        );

        $this->assertSame('Cache-Control', $cacheControl->name);
        $this->assertStringContainsString('no-cache', $cacheControl->value);
        $this->assertStringContainsString('no-store', $cacheControl->value);
        $this->assertStringContainsString('max-age=3600', $cacheControl->value);
        $this->assertStringContainsString('s-maxage=7200', $cacheControl->value);
        $this->assertStringContainsString('private', $cacheControl->value);
        $this->assertStringContainsString('must-revalidate', $cacheControl->value);
        $this->assertStringContainsString('proxy-revalidate', $cacheControl->value);
        $this->assertStringContainsString('no-transform', $cacheControl->value);
        $this->assertStringContainsString('immutable', $cacheControl->value);
        $this->assertStringContainsString('stale-while-revalidate=3600', $cacheControl->value);
        $this->assertStringContainsString('stale-if-error=7200', $cacheControl->value);
    }

    public function testToString(): void
    {
        $cacheControl = new CacheControl();

        $this->assertSame('Cache-Control: public', (string) $cacheControl);
    }
}
