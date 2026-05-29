<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc;

use PhpMvc\Files\DefaultFileManager;
use PhpMvc\Files\FileManager;
use PhpMvc\LanguageSettings;
use PhpMvc\Middlewares\ErrorHandling;
use PhpMvc\Middlewares\Localization;
use PhpMvc\MutableContainerInterface;
use PhpMvc\MvcWebApp;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Requests\RequestHandler;
use PhpMvc\Routes\Router;
use PhpMvc\Views\HtmlViewEngine;
use PhpMvc\Views\ViewEngine;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Support\Psr7\TestPsr17Factory;

/**
 * @internal
 *
 * @coversNothing
 */
final class MvcWebAppTest extends TestCase
{
    public function testHandleReturnsPipelineResponse(): void
    {
        $factory = new TestPsr17Factory();
        $expectedResponse = $factory->createResponse(200);
        $request = $factory->createServerRequest('GET', '/');

        $passThrough = $this->createStub(MiddlewareInterface::class);
        $passThrough->method('process')->willReturnCallback(
            static fn (ServerRequestInterface $req, RequestHandlerInterface $next) => $next->handle($req)
        );

        $handlerStub = $this->createStub(RequestHandlerInterface::class);
        $handlerStub->method('handle')->willReturn($expectedResponse);

        $container = $this->buildContainer([
            ResponseFactoryInterface::class => $factory,
            DefaultFileManager::class => $this->createStub(FileManager::class),
            LanguageSettings::class => new LanguageSettings('/'),
            HtmlViewEngine::class => $this->createStub(ViewEngine::class),
            RequestHandler::class => $handlerStub,
            Localization::class => $passThrough,
            ErrorHandling::class => $passThrough,
        ]);

        $app = new class($container, '/') extends MvcWebApp {
            public function __construct(MutableContainerInterface $container, string $basePath)
            {
                parent::__construct($container, $basePath);
            }

            protected function router(): Router
            {
                return new Router();
            }
        };

        $result = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(200, $result->getStatusCode());
    }

    public function testHandleAttachesRequestContextToRequest(): void
    {
        $factory = new TestPsr17Factory();
        $request = $factory->createServerRequest('GET', '/');

        /** @var null|ServerRequestInterface $capturedRequest */
        $capturedRequest = null;
        $passThrough = $this->createStub(MiddlewareInterface::class);
        $passThrough->method('process')->willReturnCallback(
            static fn (ServerRequestInterface $req, RequestHandlerInterface $next) => $next->handle($req)
        );

        $handlerStub = $this->createStub(RequestHandlerInterface::class);
        $handlerStub->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $req) use ($factory, &$capturedRequest) {
                $capturedRequest = $req;

                return $factory->createResponse(200);
            }
        );

        $container = $this->buildContainer([
            ResponseFactoryInterface::class => $factory,
            DefaultFileManager::class => $this->createStub(FileManager::class),
            LanguageSettings::class => new LanguageSettings('/'),
            HtmlViewEngine::class => $this->createStub(ViewEngine::class),
            RequestHandler::class => $handlerStub,
            Localization::class => $passThrough,
            ErrorHandling::class => $passThrough,
        ]);

        $app = new class($container, '/') extends MvcWebApp {
            public function __construct(MutableContainerInterface $container, string $basePath)
            {
                parent::__construct($container, $basePath);
            }

            protected function router(): Router
            {
                return new Router();
            }
        };

        $app->handle($request);

        $this->assertNotNull($capturedRequest);
        $this->assertNotNull($capturedRequest->getAttribute(RequestContext::class));
    }

    /**
     * @param array<string, mixed> $services
     */
    private function buildContainer(array $services): MutableContainerInterface
    {
        return new class($services) implements MutableContainerInterface {
            /** @var array<string, mixed> */
            private array $registry;

            /** @param array<string, mixed> $initial */
            public function __construct(array $initial)
            {
                $this->registry = $initial;
            }

            public function get(string $id): mixed
            {
                return $this->registry[$id] ?? throw new \RuntimeException("Not registered: {$id}");
            }

            public function has(string $id): bool
            {
                return isset($this->registry[$id]);
            }

            public function set(string $id, mixed $value): void
            {
                $this->registry[$id] = $value;
            }
        };
    }
}
