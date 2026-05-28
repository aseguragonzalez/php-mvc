<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Middlewares;

use AlfonsoSG\Mvc\AuthSettings;
use AlfonsoSG\Mvc\Middlewares\Authentication;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Responses\StatusCode;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\SessionExpiredException;
use AlfonsoSG\Mvc\Security\IdentityManager;
use Tests\Support\Psr7\TestPsr17Factory;
use Tests\Support\Psr7\TestServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuthenticationTest extends TestCase
{
    private TestPsr17Factory $psrFactory;
    private AuthSettings $settings;
    private RequestContext $context;
    private IdentityManager $identityManager;
    private RequestHandlerInterface $next;

    protected function setUp(): void
    {
        $this->psrFactory = new TestPsr17Factory();
        $this->settings = new AuthSettings(
            cookieName: 'auth_token',
            signInPath: '/login',
            signOutPath: '/logout',
        );
        $this->context = new RequestContext();
        $this->identityManager = $this->createStub(IdentityManager::class);
        $next = $this->createStub(RequestHandlerInterface::class);
        $next->method('handle')->willReturn($this->psrFactory->createResponse(200));
        $this->next = $next;
    }

    public function testHandleRequestWithValidTokenSetsIdentityAndToken(): void
    {
        $user = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->activate();
        $token = 'valid_token';
        $identityManager = $this->createStub(IdentityManager::class);
        $identityManager->method('getIdentity')->willReturn($user);
        $middleware = new Authentication(
            settings: $this->settings,
            identityManager: $identityManager,
            responseFactory: $this->psrFactory,
        );

        $request = new TestServerRequest('GET', '/')
            ->withCookieParams(['auth_token' => $token])
            ->withAttribute(RequestContext::class, $this->context)
        ;

        $response = $middleware->process($request, $this->next);
        $this->assertEquals(StatusCode::Ok->value, $response->getStatusCode());
        $this->assertSame($user, $this->context->getAs('identity', UserIdentity::class));
        $this->assertSame($token, $this->context->get('identity_token'));
    }

    public function testHandleRequestWithExpiredSessionRedirects(): void
    {
        $identityManager = $this->createStub(IdentityManager::class);
        $identityManager->method('getIdentity')->willThrowException(
            new SessionExpiredException()
        );
        $middleware = new Authentication(
            settings: $this->settings,
            identityManager: $identityManager,
            responseFactory: $this->psrFactory,
        );

        $request = new TestServerRequest('GET', '/')
            ->withCookieParams(['auth_token' => 'expired_token'])
            ->withAttribute(RequestContext::class, $this->context)
        ;

        $response = $middleware->process($request, $this->next);
        $this->assertEquals(StatusCode::SeeOther->value, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
        $this->assertStringContainsString('auth_token=;', $response->getHeaderLine('Set-Cookie'));
    }
}
