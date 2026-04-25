<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\AccessDeniedException;
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
final class AccessDeniedExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/admin/users'),
            RouteController::class,
            'get',
            false,
            []
        );

        $exception = new AccessDeniedException($route);

        $this->assertStringContainsString('Access denied for route:', $exception->getMessage());
        $this->assertStringContainsString('GET /admin/users', $exception->getMessage());
    }
}
