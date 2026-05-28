<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Requests;

use AlfonsoSG\Mvc\Actions\ActionParameterBuilder;
use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\HtmlViewEngineSettings;
use AlfonsoSG\Mvc\LanguageSettings;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Requests\RequestContextKeys;
use AlfonsoSG\Mvc\Requests\RequestHandler;
use AlfonsoSG\Mvc\Routes\Path;
use AlfonsoSG\Mvc\Routes\Route;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use AlfonsoSG\Mvc\Routes\Router;
use AlfonsoSG\Mvc\Security\Identity;
use AlfonsoSG\Mvc\Views\BranchesReplacer;
use AlfonsoSG\Mvc\Views\ContentReplacerPipeline;
use AlfonsoSG\Mvc\Views\HtmlViewEngine;
use AlfonsoSG\Mvc\Views\I18nReplacer;
use AlfonsoSG\Mvc\Views\ModelReplacer;
use AlfonsoSG\Mvc\Views\ViewEngine;
use AlfonsoSG\Mvc\Views\ViewValueResolver;
use Tests\Support\Psr7\TestPsr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Controllers\TestController;

/**
 * @internal
 *
 * @coversNothing
 */
final class RequestHandlerTest extends TestCase
{
    private ActionParameterBuilder $actionParameterBuilder;
    private ContainerInterface $container;
    private TestPsr17Factory $requestFactory;
    private ResponseFactoryInterface $responseFactory;
    private Router $router;
    private HtmlViewEngineSettings $settings;
    private ViewEngine $viewEngine;
    private RequestHandler $requestHandler;

    protected function setUp(): void
    {
        $containerMock = $this->createStub(ContainerInterface::class);
        $containerMock->method('get')->willReturn(new TestController());
        $this->actionParameterBuilder = new ActionParameterBuilder();
        $this->container = $containerMock;
        $this->requestFactory = new TestPsr17Factory();
        $this->responseFactory = new TestPsr17Factory();
        $this->router = new Router(routes: [
            Route::create(RouteMethod::Get, Path::create('/test'), TestController::class, 'index'),
            Route::create(RouteMethod::Get, Path::create('/test/redirect'), TestController::class, 'redirect'),
            Route::create(RouteMethod::Get, Path::create('/test/get'), TestController::class, 'get'),
            Route::create(RouteMethod::Get, Path::create('/test/get2'), TestController::class, 'getWithOptionals'),
            Route::create(RouteMethod::Get, Path::create('/test/search'), TestController::class, 'search'),
            Route::create(RouteMethod::Get, Path::create('/test/find'), TestController::class, 'find'),
            Route::create(RouteMethod::Get, Path::create('/test/{int:id}/list'), TestController::class, 'list'),
            Route::create(RouteMethod::Post, Path::create('/test'), TestController::class, 'edit'),
            Route::create(RouteMethod::Post, Path::create('/test/{int:id}'), TestController::class, 'edit'),
            Route::create(RouteMethod::Post, Path::create('/test/{int:id}/save'), TestController::class, 'save'),
            Route::create(RouteMethod::Post, Path::create('/test/delete'), TestController::class, 'delete'),
            Route::create(RouteMethod::Post, Path::create('/test/custom'), TestController::class, 'custom'),
            Route::create(RouteMethod::Post, Path::create('/test/failed'), TestController::class, 'failed'),
            Route::create(
                RouteMethod::Get,
                Path::create('/test/local-redirect'),
                TestController::class,
                'localRedirect'
            ),
            Route::create(
                RouteMethod::Get,
                Path::create('/test/failed-local-redirect'),
                TestController::class,
                'failedLocalRedirect'
            ),
            Route::create(
                RouteMethod::Get,
                Path::create('/test/use-request'),
                TestController::class,
                'getFromRequest'
            ),
        ]);
        $this->settings = new HtmlViewEngineSettings(basePath: __DIR__);
        $resolver = new ViewValueResolver();
        $pipeline = new ContentReplacerPipeline([
            new ModelReplacer($resolver),
            new BranchesReplacer($resolver),
            new I18nReplacer(new LanguageSettings(basePath: __DIR__), $this->createStub(FileManager::class)),
        ]);
        $this->viewEngine = new HtmlViewEngine($this->settings, $pipeline);
        $this->requestHandler = new RequestHandler(
            $this->actionParameterBuilder,
            $this->container,
            $this->responseFactory,
            $this->router,
            $this->viewEngine
        );
    }

    public function testHandleRequestWithRedirectTo(): void
    {
        $uri = $this->requestFactory->createUri('/test/redirect');
        $request = $this->requestFactory->createServerRequest('GET', $uri);

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertSame('http://test.com', $response->getHeaderLine('Location'));
        $this->assertEmpty((string) $response->getBody());
    }

    public function testHandleGetRequest(): void
    {
        $uri = $this->requestFactory->createUri('/test');
        $request = $this->requestFactory
            ->createServerRequest('GET', $uri)
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_index.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    public function testHandleGetRequestWithArgs(): void
    {
        $uri = $this->requestFactory->createUri('/test/get');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['offset' => 10, 'limit' => 20])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_get.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    public function testHandleGetRequestWithOptionalArgs(): void
    {
        $uri = $this->requestFactory->createUri('/test/get2');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams([])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_get.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    public function testHandleGetRequestWithArgsAndRequestObject(): void
    {
        $uri = $this->requestFactory->createUri('/test/search');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['offset' => 1, 'limit' => 20, 'name' => 'John'])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_search.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    public function testHandleGetRequestWithQueryParams(): void
    {
        $uri = $this->requestFactory->createUri('/test/find');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['offset' => 10, 'limit' => 20])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_find.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    public function testHandleGetRequestWithPathAndQueryParams(): void
    {
        $uri = $this->requestFactory->createUri('/test/10/list');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['offset' => 10, 'limit' => 20])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_list.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    public function testHandleLocalRedirectToControllerAction(): void
    {
        $uri = $this->requestFactory->createUri('/test/local-redirect');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['offset' => 10, 'limit' => 20])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $defaultHost = getenv('DEFAULT_HOST') ?: '';
        $this->assertSame("{$defaultHost}/test/get?offset=10&limit=20", $response->getHeaderLine('Location'));
        $this->assertEmpty((string) $response->getBody());
    }

    public function testHandleLocalRedirectToControllerActionWithSourceOrigin(): void
    {
        $uri = $this->requestFactory->createUri('/test/local-redirect');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['offset' => 10, 'limit' => 20])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
            ->withHeader('Origin', 'http://example.com')
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertSame('http://example.com/test/get?offset=10&limit=20', $response->getHeaderLine('Location'));
        $this->assertEmpty((string) $response->getBody());
    }

    public function testHandleLocalRedirectToControllerActionFailIfRouteNotFound(): void
    {
        $uri = $this->requestFactory->createUri('/test/failed-local-redirect');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Route not found for controller: '
            .TestController::class
            .', action: failedLocalRedirectTarget'
        );
        $this->requestHandler->handle($request);
    }

    public function testHandleGetRequestUsingRequestObject(): void
    {
        $uri = $this->requestFactory->createUri('/test/use-request');
        $request = $this->requestFactory->createServerRequest('GET', $uri)
            ->withQueryParams(['param1' => 'value1', 'param2' => 'value2'])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[DataProvider('postProvider')]
    public function testHandlePostRequest(string $contentType): void
    {
        $uri = $this->requestFactory->createUri('/test/delete');
        $request = $this->requestFactory->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', $contentType)
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_delete.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    #[DataProvider('postProvider')]
    public function testHandlePostRequestWithBodyParams(string $contentType): void
    {
        $uri = $this->requestFactory->createUri('/test');
        $request = $this->requestFactory->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', $contentType)
            ->withParsedBody(['name' => 'John Doe', 'email' => 'john.doe@gmail.com', 'id' => 10])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_edit.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    #[DataProvider('postProvider')]
    public function testHandlePostRequestWithPathAndBodyParams(string $contentType): void
    {
        $uri = $this->requestFactory->createUri('/test/10');
        $request = $this->requestFactory->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', $contentType)
            ->withParsedBody(['name' => 'John Doe', 'email' => 'john.doe@gmail.com'])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_edit.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    #[DataProvider('postProvider')]
    public function testHandlePostRequestWithPathQueryAndBodyParams(string $contentType): void
    {
        $uri = $this->requestFactory->createUri('/test/10/save');
        $request = $this->requestFactory->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', $contentType)
            ->withQueryParams(['offset' => 10, 'limit' => 20])
            ->withParsedBody(['name' => 'John Doe', 'email' => 'john.doe@gmail.com'])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_save.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    #[DataProvider('postProvider')]
    public function testHandlePostRequestWithActionArguments(string $contentType): void
    {
        $uri = $this->requestFactory->createUri('/test/custom');
        $request = $this->requestFactory->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', $contentType)
            ->withParsedBody(['id' => 10, 'amount' => 100.01, 'name' => 'John Doe'])
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $response = $this->requestHandler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $expectedContent = file_get_contents(__DIR__.'/Files/expected_custom.html');
        $this->assertSame($expectedContent, (string) $response->getBody());
    }

    #[DataProvider('postProvider')]
    public function testHandlePostRequestFailWhenReturnsNoActionResponse(string $contentType): void
    {
        $uri = $this->requestFactory->createUri('/test/failed');
        $request = $this->requestFactory->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', $contentType)
            ->withAttribute(RequestContext::class, $this->getRequestContext())
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Response object returned from controller');
        $this->requestHandler->handle($request);
    }

    /**
     * @return array<array{string}>
     */
    public static function postProvider(): array
    {
        return [
            ['application/x-www-form-urlencoded'],
            ['application/json'],
            ['multipart/form-data'],
        ];
    }

    private function getRequestContext(): RequestContext
    {
        $requestContext = new RequestContext();
        $requestContext->set(RequestContextKeys::Language->value, 'en');
        $identity = $this->createStub(Identity::class);
        $identity->method('isAuthenticated')->willReturn(false);
        $identity->method('hasRole')->willReturn(false);
        $identity->method('username')->willReturn('anonymous');
        $requestContext->setIdentity($identity);

        return $requestContext;
    }
}
