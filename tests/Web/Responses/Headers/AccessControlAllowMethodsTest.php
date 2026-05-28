<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Responses\Headers;

use PhpMvc\Responses\Headers\AccessControlAllowMethods;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AccessControlAllowMethodsTest extends TestCase
{
    public function testDefaultMethods(): void
    {
        $header = new AccessControlAllowMethods();

        $this->assertSame('Access-Control-Allow-Methods', $header->name);
        $this->assertSame('*', $header->value);
    }

    public function testCustomMethods(): void
    {
        $header = new AccessControlAllowMethods(
            get: false,
            post: false,
            put: false,
            delete: false,
            options: false,
            head: false,
            patch: false,
            connect: false,
            trace: false
        );

        $this->assertSame('Access-Control-Allow-Methods', $header->name);
        $this->assertSame('', $header->value);
    }

    public function testSingleMethod(): void
    {
        $header = new AccessControlAllowMethods(
            post: false,
            put: false,
            delete: false,
            options: false,
            head: false,
            patch: false,
            connect: false,
            trace: false
        );

        $this->assertSame('Access-Control-Allow-Methods', $header->name);
        $this->assertSame('GET', $header->value);
    }

    public function testMultipleMethods(): void
    {
        $header = new AccessControlAllowMethods(
            put: false,
            delete: false,
            options: false,
            head: false,
            patch: false,
            connect: false,
            trace: false
        );

        $this->assertSame('Access-Control-Allow-Methods', $header->name);
        $this->assertStringContainsString('GET', $header->value);
        $this->assertStringContainsString('POST', $header->value);
    }

    public function testAllMethodsExceptOne(): void
    {
        $header = new AccessControlAllowMethods(trace: false);

        $this->assertSame('Access-Control-Allow-Methods', $header->name);
        $this->assertStringContainsString('GET', $header->value);
        $this->assertStringContainsString('POST', $header->value);
        $this->assertStringContainsString('PUT', $header->value);
        $this->assertStringContainsString('DELETE', $header->value);
        $this->assertStringContainsString('OPTIONS', $header->value);
        $this->assertStringContainsString('HEAD', $header->value);
        $this->assertStringContainsString('PATCH', $header->value);
        $this->assertStringContainsString('CONNECT', $header->value);
    }

    public function testToString(): void
    {
        $header = new AccessControlAllowMethods(
            get: true,
            post: true,
            put: false,
            delete: false,
            options: true,
            head: false,
            patch: false,
            connect: false,
            trace: false
        );

        $this->assertSame('Access-Control-Allow-Methods: GET, POST, OPTIONS', (string) $header);
    }
}
