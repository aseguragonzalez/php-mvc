<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Routes;

use PhpMvc\Routes\AuthenticationRequiredException;
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
