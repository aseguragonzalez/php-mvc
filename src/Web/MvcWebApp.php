<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc;

use AlfonsoSG\Mvc\Files\DefaultFileManager;
use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\Middlewares\Authentication;
use AlfonsoSG\Mvc\Middlewares\Authorization;
use AlfonsoSG\Mvc\Middlewares\CsrfProtection;
use AlfonsoSG\Mvc\Middlewares\ErrorHandling;
use AlfonsoSG\Mvc\Middlewares\Localization;
use AlfonsoSG\Mvc\Middlewares\Middleware;
use AlfonsoSG\Mvc\Middlewares\MiddlewarePipeline;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Requests\RequestHandler;
use AlfonsoSG\Mvc\Routes\Router;
use AlfonsoSG\Mvc\Views\BranchesReplacer;
use AlfonsoSG\Mvc\Views\ContentReplacer;
use AlfonsoSG\Mvc\Views\ContentReplacerPipeline;
use AlfonsoSG\Mvc\Views\HtmlViewEngine;
use AlfonsoSG\Mvc\Views\I18nReplacer;
use AlfonsoSG\Mvc\Views\ModelReplacer;
use AlfonsoSG\Mvc\Views\ViewEngine;
use AlfonsoSG\Mvc\Views\ViewValueResolver;
use AlfonsoSG\Mvc\MutableContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The base class for all MVC Web applications.
 */
abstract class MvcWebApp extends Application
{
    /**
     * @param string                          $basePath              the base path of the application
     * @param array<class-string<Middleware>> $middlewares           the middlewares to use
     * @param bool                            $requireAuthentication whether to require authentication
     * @param bool                            $requireAuthorization  whether to require authorization
     * @param bool                            $enableCsrfProtection  whether to validate CSRF tokens on state-changing requests
     */
    protected function __construct(
        private readonly MutableContainerInterface $diContainer,
        string $basePath,
        private array $middlewares = [],
        private bool $requireAuthentication = false,
        private bool $requireAuthorization = false,
        private bool $enableCsrfProtection = false,
    ) {
        parent::__construct($diContainer, $basePath);
    }

    /**
     * @param null|int      $argc The number of arguments passed to the application. Default is null.
     * @param array<string> $argv The arguments to pass to the application. Default is an empty array.
     *
     * @return int the exit code of the application
     */
    public function run(?int $argc = null, array $argv = []): int
    {
        $requestContext = new RequestContext();
        $this->diContainer->set(RequestContext::class, $requestContext);
        $this->configureMvc();
        $this->buildMiddlewareChain();
        $this->handleRequest($requestContext);

        return 0;
    }

    /**
     * @param class-string<Middleware> $middleware
     */
    public function addMiddleware(string $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Require authentication for the application.
     */
    public function useAuthentication(): void
    {
        $this->requireAuthentication = true;
    }

    /**
     * Require authorization for the application.
     */
    public function useAuthorization(): void
    {
        $this->requireAuthorization = true;
    }

    /**
     * Enable CSRF protection for state-changing HTTP methods.
     */
    public function useCsrfProtection(): void
    {
        $this->enableCsrfProtection = true;
    }

    /**
     * Configure the router for the application.
     */
    abstract protected function router(): Router;

    private function buildMiddlewareChain(): void
    {
        /** @var RequestHandlerInterface $pipeline */
        $pipeline = $this->diContainer->get(RequestHandlerInterface::class);

        foreach (array_reverse($this->middlewares) as $middlewareClass) {
            /** @var Middleware $middleware */
            $middleware = $this->diContainer->get($middlewareClass);
            $pipeline = new MiddlewarePipeline($middleware, $pipeline);
        }

        if ($this->requireAuthorization && $this->requireAuthentication) {
            $pipeline = new MiddlewarePipeline($this->diContainer->get(Authorization::class), $pipeline);
        }

        if ($this->requireAuthentication) {
            $pipeline = new MiddlewarePipeline($this->diContainer->get(Authentication::class), $pipeline);
        }

        if ($this->enableCsrfProtection) {
            $pipeline = new MiddlewarePipeline($this->diContainer->get(CsrfProtection::class), $pipeline);
        }

        $pipeline = new MiddlewarePipeline($this->diContainer->get(Localization::class), $pipeline);
        $pipeline = new MiddlewarePipeline($this->diContainer->get(ErrorHandling::class), $pipeline);

        $this->diContainer->set(RequestHandlerInterface::class, $pipeline);
    }

    private function configureMvc(): void
    {
        $responseFactory = $this->diContainer->get(ResponseFactoryInterface::class);
        if (!$responseFactory instanceof ResponseFactoryInterface) {
            throw new \RuntimeException(
                'ResponseFactoryInterface must be registered in the container before running the application.'
            );
        }
        $this->diContainer->set(Router::class, $this->router());
        $this->diContainer->set(FileManager::class, $this->diContainer->get(DefaultFileManager::class));
        $resolver = new ViewValueResolver();
        $languageSettings = $this->diContainer->get(LanguageSettings::class);
        $fileManager = $this->diContainer->get(FileManager::class);
        if (!$languageSettings instanceof LanguageSettings || !$fileManager instanceof FileManager) {
            throw new \RuntimeException('LanguageSettings or FileManager not found in container');
        }
        $this->diContainer->set(
            ContentReplacer::class,
            new ContentReplacerPipeline([
                new ModelReplacer($resolver),
                new BranchesReplacer($resolver),
                new I18nReplacer($languageSettings, $fileManager),
            ])
        );
        $this->diContainer->set(ViewEngine::class, $this->diContainer->get(HtmlViewEngine::class));
        $this->diContainer->set(RequestHandlerInterface::class, $this->diContainer->get(RequestHandler::class));
    }

    private function handleRequest(RequestContext $requestContext): void
    {
        $pipeline = $this->diContainer->get(RequestHandlerInterface::class);
        if (!$pipeline instanceof RequestHandlerInterface) {
            throw new \RuntimeException('RequestHandlerInterface not found in container');
        }

        $request = $this->createRequestFromGlobals();
        $response = $pipeline->handle(
            $request->withAttribute(RequestContext::class, $requestContext)
        );

        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
        echo $response->getBody();
    }

    private function createRequestFromGlobals(): ServerRequestInterface
    {
        $server = $_SERVER;
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $scheme = (!empty($server['HTTPS']) && 'off' !== $server['HTTPS']) ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? ($server['SERVER_NAME'] ?? 'localhost');
        $uri = $scheme . '://' . $host . ($server['REQUEST_URI'] ?? '/');

        $requestFactory = $this->diContainer->get(ServerRequestFactoryInterface::class);
        if (!$requestFactory instanceof ServerRequestFactoryInterface) {
            throw new \RuntimeException(
                'ServerRequestFactoryInterface must be registered in the container before running the application.'
            );
        }

        $request = $requestFactory->createServerRequest($method, $uri, $server);

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $request = $request->withHeader(str_replace('_', '-', substr($key, 5)), $value);
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) && '' !== $value) {
                $request = $request->withHeader(str_replace('_', '-', $key), $value);
            }
        }

        $request = $request
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
        ;

        $contentType = $server['CONTENT_TYPE'] ?? '';
        if (str_starts_with($contentType, 'application/json')) {
            $rawBody = (string) file_get_contents('php://input');
            $parsed = json_decode($rawBody, true);
            if (is_array($parsed)) {
                $request = $request->withParsedBody($parsed);
            }
        } elseif (!empty($_POST)) {
            $request = $request->withParsedBody($_POST);
        }

        return $request;
    }
}
