<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\DuplicatedRouteException;
use AlfonsoSG\Mvc\Routes\Path;
use AlfonsoSG\Mvc\Routes\Route;
use AlfonsoSG\Mvc\Routes\RouteDoesNotFoundException;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use AlfonsoSG\Mvc\Routes\Router;
use PHPUnit\Framework\TestCase;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Routes\Router\RouterController;

/**
 * @internal
 *
 * @coversNothing
 */
final class RouterTest extends TestCase
{
    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testRegister(): void
    {
        $route = Route::create(RouteMethod::Get, Path::create('/test'), RouterController::class, 'get');
        $router = new Router();

        $router->register($route);

        $this->assertEquals([$route], $router->getRoutes());
    }

    public function testRegisterFailsWhenRouteIsDuplicated(): void
    {
        $route = Route::create(RouteMethod::Get, Path::create('/test'), RouterController::class, 'get');
        $router = new Router();
        $router->register($route);

        $this->expectException(DuplicatedRouteException::class);
        $this->expectExceptionMessage('Route already registered: GET /test');
        $router->register($route);
    }

    public function testGet(): void
    {
        $route = Route::create(RouteMethod::Get, Path::create('/test'), RouterController::class, 'get');
        $router = new Router(routes: [
            $route,
            Route::create(RouteMethod::Get, Path::create('/other1'), RouterController::class, 'get'),
            Route::create(RouteMethod::Get, Path::create('/other2'), RouterController::class, 'get'),
        ]);

        $this->assertEquals($route, $router->get(RouteMethod::Get, '/test'));
    }

    public function testGetFailsWhenRouteIsNotFound(): void
    {
        $router = new Router();

        $this->expectException(RouteDoesNotFoundException::class);
        $this->expectExceptionMessage('Route not found: GET /test');
        $router->get(RouteMethod::Get, '/test');
    }

    public function testGetFromControllerAndActionReturnsRoute(): void
    {
        $route = Route::create(RouteMethod::Get, Path::create('/test'), RouterController::class, 'get');
        $router = new Router(routes: [
            $route,
            Route::create(RouteMethod::Get, Path::create('/other1'), RouterController::class, 'get'),
        ]);

        $result = $router->getFromControllerAndAction(RouterController::class, 'get');
        $this->assertEquals($route, $result);
    }

    public function testGetFromControllerAndActionReturnsNullWhenNotFound(): void
    {
        $router = new Router(routes: [
            Route::create(RouteMethod::Get, Path::create('/other1'), RouterController::class, 'get'),
        ]);

        $result = $router->getFromControllerAndAction(RouterController::class, 'nonexistent');
        $this->assertNull($result);
    }
}
