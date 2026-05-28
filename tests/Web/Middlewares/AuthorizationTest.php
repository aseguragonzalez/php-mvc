<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Middlewares;

use AlfonsoSG\Mvc\AuthSettings;
use AlfonsoSG\Mvc\Middlewares\Authorization;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Routes\AccessDeniedException;
use AlfonsoSG\Mvc\Routes\Path;
use AlfonsoSG\Mvc\Routes\Route;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use AlfonsoSG\Mvc\Routes\Router;
use AlfonsoSG\Mvc\Security\Identity;
use Tests\Support\Psr7\TestPsr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Routes\Route\RouteController;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuthorizationTest extends TestCase
{
    private const PUBLIC_ROUTE = '/public';
    private const PRIVATE_ROUTE = '/private';
    private const NO_ROLES_ROUTE = '/no-roles';
    private const ADMIN_ROLE = 'admin';
    private const USER_ROLE = 'user';
    private Authorization $middleware;
    private AuthSettings $settings;

    protected function setUp(): void
    {
        $this->settings = new AuthSettings(
            cookieName: 'auth_token',
            signInPath: '/login',
            signOutPath: '/logout',
        );
        $this->middleware = new Authorization(
            settings: $this->settings,
            responseFactory: new TestPsr17Factory(),
            router: self::createRouter(),
        );
    }

    public function testHandleRequestEnsureAuthenticatedAndAuthorizedUser(): void
    {
        $request = $this->createRequestStub(self::PRIVATE_ROUTE, true, [self::ADMIN_ROLE, self::USER_ROLE]);
        $response = $this->createStub(ResponseInterface::class);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())->method('handle')->with($request)->willReturn($response);

        $result = $this->middleware->process($request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleRequestRedirectsWhenNotAuthenticated(): void
    {
        $request = $this->createRequestStub(path: self::PRIVATE_ROUTE);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $next);

        $this->assertEquals(303, $result->getStatusCode());
        $this->assertEquals($this->settings->signInPath, $result->getHeaderLine('Location'));
        $this->assertStringContainsString('auth_token=;', $result->getHeaderLine('Set-Cookie'));
    }

    public function testHandleRequestPassesThroughWhenRouteNotRequiringAuth(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $request = $this->createRequestStub(path: self::PUBLIC_ROUTE);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())->method('handle')->with($request)->willReturn($response);

        $result = $this->middleware->process($request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleRequestThrowsAccessDeniedWhenUserHasNoRoles(): void
    {
        $request = $this->createRequestStub(path: self::PRIVATE_ROUTE, isAuthenticated: true, roles: []);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->never())->method('handle');
        $this->expectException(AccessDeniedException::class);

        $this->middleware->process($request, $next);
    }

    public function testHandleRequestThrowsAccessDeniedWhenRolesMismatch(): void
    {
        $request = $this->createRequestStub(path: self::PRIVATE_ROUTE, isAuthenticated: true, roles: ['guest']);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->never())->method('handle');
        $this->expectException(AccessDeniedException::class);

        $this->middleware->process($request, $next);
    }

    public function testHandleRequestPassesThroughWhenRouteHasNoRoleRequirements(): void
    {
        $request = $this->createRequestStub(path: self::NO_ROLES_ROUTE, isAuthenticated: true, roles: ['any-role']);
        $response = $this->createStub(ResponseInterface::class);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())->method('handle')->with($request)->willReturn($response);

        $result = $this->middleware->process($request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleRequestPassesThroughWhenUserHasPartialRoleMatch(): void
    {
        $request = $this->createRequestStub(path: self::PRIVATE_ROUTE, isAuthenticated: true, roles: [self::USER_ROLE]);
        $response = $this->createStub(ResponseInterface::class);
        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())->method('handle')->with($request)->willReturn($response);

        $result = $this->middleware->process($request, $next);

        $this->assertSame($response, $result);
    }

    private static function createRouter(): Router
    {
        $route = Route::create(
            RouteMethod::Get,
            Path::create(self::PRIVATE_ROUTE),
            RouteController::class,
            'get',
            true,
            [self::ADMIN_ROLE, self::USER_ROLE]
        );
        $publicRoute = Route::create(
            RouteMethod::Get,
            Path::create(self::PUBLIC_ROUTE),
            RouteController::class,
            'get',
            false,
            []
        );
        $noRolesRoute = Route::create(
            RouteMethod::Get,
            Path::create(self::NO_ROLES_ROUTE),
            RouteController::class,
            'get',
            true,
            []
        );

        return new Router([$route, $publicRoute, $noRolesRoute]);
    }

    /**
     * @param array<string> $roles
     */
    private function createRequestStub(
        string $path,
        bool $isAuthenticated = false,
        array $roles = [],
    ): ServerRequestInterface {
        $identity = $this->createStub(Identity::class);
        $identity->method('isAuthenticated')->willReturn($isAuthenticated);
        $identity->method('getRoles')->willReturn($roles);

        $context = new RequestContext();
        $context->setIdentity($identity);

        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getAttribute')->willReturn($context);

        return $request;
    }
}
