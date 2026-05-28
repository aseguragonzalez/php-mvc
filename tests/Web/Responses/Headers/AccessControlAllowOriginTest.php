<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Responses\Headers;

use PhpMvc\Responses\Headers\AccessControlAllowOrigin;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AccessControlAllowOriginTest extends TestCase
{
    public function testAnyReturnsHeaderWithWildcard(): void
    {
        $header = AccessControlAllowOrigin::any();

        $this->assertSame('Access-Control-Allow-Origin', $header->name);
        $this->assertSame('*', $header->value);
    }

    public function testNoneReturnsHeaderWithNull(): void
    {
        $header = AccessControlAllowOrigin::none();

        $this->assertSame('Access-Control-Allow-Origin', $header->name);
        $this->assertSame('null', $header->value);
    }

    public function testSpecificReturnsHeaderWithGivenOrigin(): void
    {
        $origin = 'https://specific.com';
        $header = AccessControlAllowOrigin::specific($origin);

        $this->assertSame('Access-Control-Allow-Origin', $header->name);
        $this->assertSame($origin, $header->value);
    }

    public function testToStringReturnsFormattedHeader(): void
    {
        $header = AccessControlAllowOrigin::specific('https://example.com');

        $this->assertSame('Access-Control-Allow-Origin: https://example.com', (string) $header);
    }
}
