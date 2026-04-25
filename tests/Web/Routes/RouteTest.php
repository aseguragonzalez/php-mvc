<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\AccessDeniedException;
use AlfonsoSG\Mvc\Routes\AuthenticationRequiredException;
use AlfonsoSG\Mvc\Routes\InvalidAction;
use AlfonsoSG\Mvc\Routes\InvalidController;
use AlfonsoSG\Mvc\Routes\Path;
use AlfonsoSG\Mvc\Routes\Route;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use AlfonsoSG\Mvc\Security\Identity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Routes\Route\RouteController;

/**
 * @internal
 *
 * @coversNothing
 */
final class RouteTest extends TestCase
{
    public function testEnsureAuthenticatedIsNotRequired(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->never())->method('isAuthenticated')->willReturn(false);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            false,
            []
        );

        $route->ensureAuthenticated($identity);
    }

    public function testEnsureAuthenticated(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->once())->method('isAuthenticated')->willReturn(true);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            true,
            []
        );

        $route->ensureAuthenticated($identity);
    }

    public function testEnsureAuthenticatedFailsWhenNotAuthenticated(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->once())->method('isAuthenticated')->willReturn(false);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            true,
            []
        );
        $this->expectException(AuthenticationRequiredException::class);

        $route->ensureAuthenticated($identity);
    }

    public function testEnsureAuthorizedDoesNotRequireRoles(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->never())->method('getRoles')->willReturn([]);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            true,
            []
        );

        $route->ensureAuthorized($identity);
    }

    public function testEnsureAuthorizedFailsWhenIdentityHasNoRoles(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->once())->method('getRoles')->willReturn([]);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            true,
            ['admin', 'user']
        );
        $this->expectException(AccessDeniedException::class);

        $route->ensureAuthorized($identity);
    }

    public function testEnsureAuthorizedFailsWhenRolesMismatch(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->once())->method('getRoles')->willReturn(['guest']);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            true,
            ['admin', 'user']
        );
        $this->expectException(AccessDeniedException::class);

        $route->ensureAuthorized($identity);
    }

    public function testEnsureAuthorizedWhenRolesMatchPartially(): void
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->once())->method('getRoles')->willReturn(['user']);
        $route = Route::create(
            RouteMethod::Get,
            Path::create('/foo'),
            RouteController::class,
            'get',
            true,
            ['admin', 'user']
        );

        $route->ensureAuthorized($identity);
    }

    /**
     * @param array<string, null|bool|float|int|string> $args
     */
    #[DataProvider('getPathFromArgsProvider')]
    public function testGetPathFromArgs(string $routePath, array $args, string $expectedPath): void
    {
        $route = Route::create(RouteMethod::Get, Path::create($routePath), RouteController::class, 'get');

        $result = $route->getPathFromArgs($args);

        $this->assertSame($expectedPath, $result->value());
    }

    /**
     * @return array<array{string, array<string, null|bool|float|int|string>, string}>
     */
    public static function getPathFromArgsProvider(): array
    {
        return [
            ['/foo/{id}', ['id' => 123], '/foo/123'],
            ['/foo/{id}/bar/{name}', ['id' => 1, 'name' => 'alice'], '/foo/1/bar/alice'],
            ['/foo/{id}', ['id' => 42, 'extra' => 'test'], '/foo/42?extra=test'],
            ['/foo/{id}', ['id' => 42, 'a' => 'x', 'b' => 2], '/foo/42?a=x&b=2'],
            ['/foo/{id}', ['id' => 42, 'a' => null, 'b' => 'ok'], '/foo/42?b=ok'],
            ['/foo/{id}', ['id' => true, 'q' => false], '/foo/1?q='],
            ['/foo', [], '/foo'],
            ['/foo/{int:id}', ['id' => 7], '/foo/7'],
            ['/foo/{float:amount}', ['amount' => 3.14], '/foo/3.14'],
            ['/foo/{id}/bar/{missing}', ['id' => 5], '/foo/5/bar/{missing}'],
        ];
    }

    public function testCreate(): void
    {
        $route = Route::create(RouteMethod::Get, Path::create('/foo'), RouteController::class, 'get');

        $this->assertSame(RouteMethod::Get, $route->method);
        $this->assertSame('/foo', $route->path->value());
        $this->assertSame(RouteController::class, $route->controller);
        $this->assertSame('get', $route->action);
    }

    public function testCreateFailWhenControllerIsInvalid(): void
    {
        $this->expectException(InvalidController::class);
        $this->expectExceptionMessage('Controller '.RouteTest::class.' is not a valid controller');

        Route::create(RouteMethod::Get, Path::create('/foo'), RouteTest::class, 'invalidAction');
    }

    public function testCreateFailWhenActionIsInvalid(): void
    {
        $this->expectException(InvalidAction::class);
        $this->expectExceptionMessage(
            "Action 'invalidAction' is not a valid action for controller ".RouteController::class
        );

        Route::create(RouteMethod::Get, Path::create('/foo'), RouteController::class, 'invalidAction');
    }

    public function testEquals(): void
    {
        $route1 = Route::create(RouteMethod::Get, Path::create('/foo'), RouteController::class, 'get');
        $route2 = Route::create(RouteMethod::Get, Path::create('/foo'), RouteController::class, 'get');
        $route3 = Route::create(RouteMethod::Get, Path::create('/bar'), RouteController::class, 'get');

        $this->assertTrue($route1->equals($route2));
        $this->assertFalse($route1->equals($route3));
    }

    #[DataProvider('routeProvider')]
    public function testMatch(string $path, string $testPath, bool $expected): void
    {
        $route = Route::create(RouteMethod::Get, Path::create($path), RouteController::class, 'get');

        $this->assertTrue($route->match(method: RouteMethod::Get, path: $testPath) === $expected);
    }

    /**
     * @return array<array{string, string, bool}>
     */
    public static function routeProvider(): array
    {
        return [
            ['/foo', '/foo', true],
            ['/bar', '/foo', false],
            ['/foo/{id}', '/foo/1', true],
            ['/foo/{id}', '/foo/1', true],
            ['/foo/{id}', '/foo/name', true],
            ['/foo/{int:id}', '/foo/name', false],
            ['/foo/{int:id}', '/foo/1', true],
            ['/foo/{int:id}/bar', '/foo/1/bar', true],
            ['/foo/{int:id}/bar', '/foo/1/baz', false],
            ['/foo/{int:id}/bar/{name}', '/foo/1/bar/baz', true],
            ['/foo/{int:id}/bar/{name}', '/foo/1/bar/1', true],
            ['/foo/{int:id}/bar/{name}', '/foo/1/baz/1', false],
            ['/foo/{uuid:id}', '/foo/1', false],
            ['/foo/{uuid:id}/bar', '/foo/123e4567-e89b-12d3-a456-426614174000/bar', true],
        ];
    }

    /**
     * @param array<string, float|int|string> $args
     */
    #[DataProvider('argsProvider')]
    public function testGetArgs(string $path, string $testPath, array $args): void
    {
        $route = Route::create(RouteMethod::Get, Path::create($path), RouteController::class, 'get');

        $this->assertSame($args, $route->getArgs($testPath));
    }

    /**
     * @return array<array{string, string, array<string, float|int|string>}>
     */
    public static function argsProvider(): array
    {
        return [
            ['/foo/{id}', '/foo/1', ['id' => '1']],
            ['/foo/{id}/bar', '/foo/1/bar', ['id' => '1']],
            ['/foo/{id}/bar/{float:amount}', '/foo/1/bar/10.01', ['id' => '1', 'amount' => 10.01]],
            ['/foo/{id}/bar/{name}', '/foo/1/bar/peter', ['id' => '1', 'name' => 'peter']],
            ['/foo/{int:id}/bar/{name}', '/foo/1/bar/peter', ['id' => 1, 'name' => 'peter']],
            [
                '/foo/{int:id}/bar/{uuid:token}',
                '/foo/10/bar/123e4567-e89b-12d3-a456-426614174000',
                ['id' => 10, 'token' => '123e4567-e89b-12d3-a456-426614174000'],
            ],
        ];
    }
}
