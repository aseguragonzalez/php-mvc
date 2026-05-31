<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Middlewares;

use PhpMvc\Middlewares\CsrfProtection;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Responses\StatusCode;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Support\Psr7\TestPsr17Factory;

/**
 * @internal
 *
 * @coversNothing
 */
final class CsrfProtectionTest extends TestCase
{
    private TestPsr17Factory $factory;
    private CsrfProtection $middleware;

    protected function setUp(): void
    {
        $this->factory = new TestPsr17Factory();
        $this->middleware = new CsrfProtection('csrf_token', $this->factory);
    }

    public function testProcessThrowsWhenRequestContextIsMissing(): void
    {
        $request = $this->factory->createServerRequest('GET', $this->factory->createUri('/'));
        $handler = $this->createStub(RequestHandlerInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RequestContext not found in request attributes');

        $this->middleware->process($request, $handler);
    }

    public function testProcessPassesGetRequestToHandlerWithoutCsrfCheck(): void
    {
        $context = new RequestContext();
        $request = $this->factory
            ->createServerRequest('GET', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
        ;

        $expectedResponse = $this->factory->createResponse(200);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessReusesExistingTokenFromCookie(): void
    {
        $context = new RequestContext();
        $existingToken = 'existing-csrf-token';
        $request = $this->factory
            ->createServerRequest('GET', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
            ->withCookieParams(['csrf_token' => $existingToken])
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->factory->createResponse(200));

        $this->middleware->process($request, $handler);

        $this->assertSame($existingToken, CsrfProtection::getTokenFromContext($context));
    }

    public function testProcessCreatesNewTokenWhenNoCookieExists(): void
    {
        $context = new RequestContext();
        $request = $this->factory
            ->createServerRequest('GET', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->factory->createResponse(200));

        $this->middleware->process($request, $handler);

        $token = CsrfProtection::getTokenFromContext($context);
        $this->assertNotEmpty($token);
    }

    public function testProcessAllowsPostWithValidCsrfTokenInBody(): void
    {
        $context = new RequestContext();
        $token = 'valid-token';
        $request = $this->factory
            ->createServerRequest('POST', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
            ->withCookieParams(['csrf_token' => $token])
            ->withParsedBody(['_csrf' => $token])
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->factory->createResponse(200));

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessReturnsForbiddenWhenCsrfTokenMissing(): void
    {
        $context = new RequestContext();
        $token = 'valid-token';
        $request = $this->factory
            ->createServerRequest('POST', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
            ->withCookieParams(['csrf_token' => $token])
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(StatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token', (string) $response->getBody());
    }

    public function testProcessReturnsForbiddenWhenCsrfTokenMismatch(): void
    {
        $context = new RequestContext();
        $request = $this->factory
            ->createServerRequest('POST', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
            ->withCookieParams(['csrf_token' => 'correct-token'])
            ->withParsedBody(['_csrf' => 'wrong-token'])
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(StatusCode::Forbidden->value, $response->getStatusCode());
    }

    public function testProcessAllowsPostWithCsrfTokenFromHeader(): void
    {
        $context = new RequestContext();
        $token = 'header-token';
        $request = $this->factory
            ->createServerRequest('POST', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
            ->withCookieParams(['csrf_token' => $token])
            ->withHeader('X-CSRF-Token', $token)
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->factory->createResponse(200));

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetTokenFromContextReturnsStoredToken(): void
    {
        $context = new RequestContext();
        $request = $this->factory
            ->createServerRequest('GET', $this->factory->createUri('/'))
            ->withAttribute(RequestContext::class, $context)
            ->withCookieParams(['csrf_token' => 'my-token'])
        ;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($this->factory->createResponse(200));

        $this->middleware->process($request, $handler);

        $this->assertSame('my-token', CsrfProtection::getTokenFromContext($context));
    }
}
