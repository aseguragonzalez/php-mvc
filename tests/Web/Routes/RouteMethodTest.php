<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\RouteMethod;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class RouteMethodTest extends TestCase
{
    public function testValue(): void
    {
        $this->assertSame('GET', RouteMethod::Get->value);
        $this->assertSame('POST', RouteMethod::Post->value);
        $this->assertSame('PUT', RouteMethod::Put->value);
        $this->assertSame('DELETE', RouteMethod::Delete->value);
        $this->assertSame('PATCH', RouteMethod::Patch->value);
        $this->assertSame('OPTIONS', RouteMethod::Options->value);
        $this->assertSame('HEAD', RouteMethod::Head->value);
        $this->assertSame('TRACE', RouteMethod::Trace->value);
        $this->assertSame('CONNECT', RouteMethod::Connect->value);
    }

    public function testEquals(): void
    {
        $this->assertTrue(RouteMethod::Get->equals('GET'));
        $this->assertTrue(RouteMethod::Post->equals('POST'));
        $this->assertTrue(RouteMethod::Put->equals('PUT'));
        $this->assertTrue(RouteMethod::Delete->equals('DELETE'));
        $this->assertTrue(RouteMethod::Patch->equals('PATCH'));
        $this->assertTrue(RouteMethod::Options->equals('OPTIONS'));
        $this->assertTrue(RouteMethod::Head->equals('HEAD'));
        $this->assertTrue(RouteMethod::Trace->equals('TRACE'));
        $this->assertTrue(RouteMethod::Connect->equals('CONNECT'));
        $this->assertTrue(RouteMethod::Get->equals('get'));
        $this->assertTrue(RouteMethod::Post->equals('post'));
        $this->assertTrue(RouteMethod::Put->equals('put'));
        $this->assertTrue(RouteMethod::Delete->equals('delete'));
        $this->assertTrue(RouteMethod::Patch->equals('patch'));
        $this->assertTrue(RouteMethod::Options->equals('options'));
        $this->assertTrue(RouteMethod::Head->equals('head'));
        $this->assertTrue(RouteMethod::Trace->equals('trace'));
        $this->assertTrue(RouteMethod::Connect->equals('connect'));
    }

    public function testFromString(): void
    {
        $this->assertSame(RouteMethod::Get, RouteMethod::fromString('GET'));
        $this->assertSame(RouteMethod::Post, RouteMethod::fromString('POST'));
        $this->assertSame(RouteMethod::Put, RouteMethod::fromString('PUT'));
        $this->assertSame(RouteMethod::Delete, RouteMethod::fromString('DELETE'));
        $this->assertSame(RouteMethod::Patch, RouteMethod::fromString('PATCH'));
        $this->assertSame(RouteMethod::Options, RouteMethod::fromString('OPTIONS'));
        $this->assertSame(RouteMethod::Head, RouteMethod::fromString('HEAD'));
        $this->assertSame(RouteMethod::Trace, RouteMethod::fromString('TRACE'));
        $this->assertSame(RouteMethod::Connect, RouteMethod::fromString('CONNECT'));
        $this->assertSame(RouteMethod::Get, RouteMethod::fromString('get'));
        $this->assertSame(RouteMethod::Post, RouteMethod::fromString('post'));
        $this->assertSame(RouteMethod::Put, RouteMethod::fromString('put'));
        $this->assertSame(RouteMethod::Delete, RouteMethod::fromString('delete'));
        $this->assertSame(RouteMethod::Patch, RouteMethod::fromString('patch'));
        $this->assertSame(RouteMethod::Options, RouteMethod::fromString('options'));
        $this->assertSame(RouteMethod::Head, RouteMethod::fromString('head'));
        $this->assertSame(RouteMethod::Trace, RouteMethod::fromString('trace'));
        $this->assertSame(RouteMethod::Connect, RouteMethod::fromString('connect'));
    }

    public function testFromStringInvalidMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid method: INVALID');
        RouteMethod::fromString('INVALID');
    }
}
