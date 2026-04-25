<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\RouteDoesNotFoundException;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RouteDoesNotFoundExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $exception = new RouteDoesNotFoundException(RouteMethod::Get, '/unknown/path');

        $this->assertSame('Route not found: GET /unknown/path', $exception->getMessage());
    }

    public function testExceptionIncludesMethodAndPath(): void
    {
        $exception = new RouteDoesNotFoundException(RouteMethod::Post, '/api/v2/resource');

        $this->assertStringContainsString('POST', $exception->getMessage());
        $this->assertStringContainsString('/api/v2/resource', $exception->getMessage());
    }
}
