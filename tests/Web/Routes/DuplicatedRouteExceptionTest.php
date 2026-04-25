<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\DuplicatedRouteException;
use AlfonsoSG\Mvc\Routes\Path;
use AlfonsoSG\Mvc\Routes\Route;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use PHPUnit\Framework\TestCase;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Routes\Route\RouteController;

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
