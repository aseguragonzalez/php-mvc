<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Routes;

use PhpMvc\Routes\AccessDeniedException;
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
