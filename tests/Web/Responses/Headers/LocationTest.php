<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Responses\Headers;

use PhpMvc\Responses\Headers\Location;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class LocationTest extends TestCase
{
    public function testLocationHeaderIsSetCorrectly(): void
    {
        $url = 'https://example.com';
        $location = Location::toUrl(url: $url);

        $this->assertSame('Location', $location->name);
        $this->assertSame($url, $location->value);
    }

    public function testLocationHeaderWithEmptyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Location::toUrl('');
    }

    public function testLocationHeaderWithSpecialCharacters(): void
    {
        $url = 'https://example.com/path?query=param&another=param';
        $location = Location::toUrl(url: $url);

        $this->assertSame('Location', $location->name);
        $this->assertSame($url, $location->value);
    }

    public function testLocationHeaderToString(): void
    {
        $url = 'https://example.com';
        $location = Location::toUrl(url: $url);

        $this->assertSame('Location: https://example.com', (string) $location);
    }

    public function testLocationHeaderThrowsExceptionForInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Location::toUrl('ftp://invalid-url.com');
    }
}
