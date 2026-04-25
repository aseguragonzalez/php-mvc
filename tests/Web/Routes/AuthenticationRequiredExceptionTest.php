<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\AuthenticationRequiredException;
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
final class AuthenticationRequiredExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/dashboard'),
            RouteController::class,
            'get',
            false,
            []
        );

        $exception = new AuthenticationRequiredException($route);

        $this->assertStringContainsString('Authentication required for route:', $exception->getMessage());
        $this->assertStringContainsString('GET /dashboard', $exception->getMessage());
    }
}
