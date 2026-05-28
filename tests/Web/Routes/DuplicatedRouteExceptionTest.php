<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Routes;

use PhpMvc\Routes\DuplicatedRouteException;
use PhpMvc\Routes\Path;
use PhpMvc\Routes\Route;
use PhpMvc\Routes\RouteMethod;
use PHPUnit\Framework\TestCase;
use Tests\Unit\PhpMvc\Fixtures\Routes\Route\RouteController;

/**
 * @internal
 *
 * @coversNothing
 */
final class DuplicatedRouteExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $route = Route::create(
            RouteMethod::Post,
            Path::create('/api/resource'),
            RouteController::class,
            'get',
            false,
            []
        );

        $exception = new DuplicatedRouteException($route);

        $this->assertStringContainsString('Route already registered:', $exception->getMessage());
        $this->assertStringContainsString('POST /api/resource', $exception->getMessage());
    }
}
