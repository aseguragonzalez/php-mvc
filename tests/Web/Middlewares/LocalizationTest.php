<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Middlewares;

use AlfonsoSG\Mvc\LanguageSettings;
use AlfonsoSG\Mvc\Middlewares\Localization;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Responses\Headers\SetCookie;
use AlfonsoSG\Mvc\Responses\StatusCode;
use Tests\Support\Psr7\TestPsr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class LocalizationTest extends TestCase
{
    private TestPsr17Factory $requestFactory;
    private Localization $middleware;
    private RequestHandlerInterface $next;
    private string $cookieName = 'lang';
    private string $defaultValue = 'en';

    protected function setUp(): void
    {
        $this->requestFactory = new TestPsr17Factory();
        $next = $this->createStub(RequestHandlerInterface::class);
        $next->method('handle')->willReturn($this->requestFactory->createResponse(200));
        $this->next = $next;
        $this->middleware = new Localization(
            settings: new LanguageSettings(
                basePath: __DIR__,
                assetsPath: 'assets/i18n',
                languages: ['en', 'es', 'fr'],
                cookieName: $this->cookieName,
                defaultValue: $this->defaultValue,
                setUrl: '/set-language',
            ),
            responseFactory: new TestPsr17Factory(),
        );
    }

    public function testHandleRequestSetsLanguageCookieOnPost(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('POST', '/set-language')
            ->withParsedBody(['language' => 'es'])
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withAddedHeader('Accept-Language', 'es')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;

        $response = $this->middleware->process($request, $this->next);

        $setCookieHeader = SetCookie::createSecureCookie(
            cookieName: $this->cookieName,
            cookieValue: 'es',
            expires: -1,
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::Found->value, $response->getStatusCode());
        $this->assertEquals($setCookieHeader->value, $response->getHeaderLine('Set-Cookie'));
        $this->assertEquals('es', $response->getHeaderLine('Content-Language'));
    }

    public function testHandleRequestSetsDefaultLanguageFromBodyOnPost(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('POST', '/set-language')
            ->withParsedBody([])
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withAddedHeader('Accept-Language', 'fr')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;

        $response = $this->middleware->process($request, $this->next);

        $setCookieHeader = SetCookie::createSecureCookie(
            cookieName: $this->cookieName,
            cookieValue: $this->defaultValue,
            expires: -1,
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::Found->value, $response->getStatusCode());
        $this->assertEquals($setCookieHeader->value, $response->getHeaderLine('Set-Cookie'));
        $this->assertEquals($this->defaultValue, $response->getHeaderLine('Content-Language'));
    }

    public function testHandleRequestSetsDefaultLanguageOnInvalidPost(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('POST', '/set-language')
            ->withParsedBody(['language' => 'xx'])
            ->withAddedHeader('Accept-Language', 'es')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;

        $response = $this->middleware->process($request, $this->next);

        $setCookieHeader = SetCookie::createSecureCookie(
            cookieName: $this->cookieName,
            cookieValue: $this->defaultValue,
            expires: -1,
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::Found->value, $response->getStatusCode());
        $this->assertEquals($setCookieHeader->value, $response->getHeaderLine('Set-Cookie'));
        $this->assertEquals($this->defaultValue, $response->getHeaderLine('Content-Language'));
    }

    public function testHandleRequestWithValidLanguageCookie(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('GET', '/any-uri')
            ->withCookieParams([$this->cookieName => 'es'])
            ->withAddedHeader('Accept-Language', 'fr;q=0.8')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;

        $response = $this->middleware->process($request, $this->next);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::Ok->value, $response->getStatusCode());
        $this->assertEquals('es', $response->getHeaderLine('Content-Language'));
    }

    public function testHandleRequestWithNoLanguageCookieFallsBackToHeader(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('GET', '/any-uri')
            ->withAddedHeader('Accept-Language', 'fr;q=0.8')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;

        $response = $this->middleware->process($request, $this->next);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::Ok->value, $response->getStatusCode());
        $this->assertEquals('fr', $response->getHeaderLine('Content-Language'));
    }

    public function testHandleRequestWithNoLanguageHeaderUsesDefault(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('GET', '/any-uri')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;

        $response = $this->middleware->process($request, $this->next);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::Ok->value, $response->getStatusCode());
        $this->assertEquals($this->defaultValue, $response->getHeaderLine('Content-Language'));
    }

    public function testLocalizationMiddlewareFailsIfNoRequestContext(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RequestContext not found in request attributes');
        $request = $this->requestFactory->createServerRequest('GET', '/any-uri');
        $this->middleware->process($request, $this->next);
    }
}
