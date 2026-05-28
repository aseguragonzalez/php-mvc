<?php

declare(strict_types=1);

namespace PhpMvc;

use PhpMvc\Files\DefaultFileManager;
use PhpMvc\Files\FileManager;
use PhpMvc\Middlewares\Authentication;
use PhpMvc\Middlewares\Authorization;
use PhpMvc\Middlewares\CsrfProtection;
use PhpMvc\Middlewares\ErrorHandling;
use PhpMvc\Middlewares\Localization;
use PhpMvc\Middlewares\Middleware;
use PhpMvc\Middlewares\MiddlewarePipeline;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Requests\RequestHandler;
use PhpMvc\Routes\Router;
use PhpMvc\Views\BranchesReplacer;
use PhpMvc\Views\ContentReplacer;
use PhpMvc\Views\ContentReplacerPipeline;
use PhpMvc\Views\HtmlViewEngine;
use PhpMvc\Views\I18nReplacer;
use PhpMvc\Views\ModelReplacer;
use PhpMvc\Views\ViewEngine;
use PhpMvc\Views\ViewValueResolver;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The base class for all MVC Web applications.
 */
abstract class MvcWebApp implements RequestHandlerInterface
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
        protected string $basePath,
        private array $middlewares = [],
        private bool $requireAuthentication = false,
        private bool $requireAuthorization = false,
        private bool $enableCsrfProtection = false,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestContext = new RequestContext();
        $this->diContainer->set(RequestContext::class, $requestContext);
        $this->configureMvc();
        $this->buildMiddlewareChain();

        $pipeline = $this->diContainer->get(RequestHandlerInterface::class);
        if (!$pipeline instanceof RequestHandlerInterface) {
            throw new \RuntimeException('RequestHandlerInterface not found in container');
        }

        return $pipeline->handle($request->withAttribute(RequestContext::class, $requestContext));
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
            /** @var Middleware $authz */
            $authz = $this->diContainer->get(Authorization::class);
            $pipeline = new MiddlewarePipeline($authz, $pipeline);
        }

        if ($this->requireAuthentication) {
            /** @var Middleware $authn */
            $authn = $this->diContainer->get(Authentication::class);
            $pipeline = new MiddlewarePipeline($authn, $pipeline);
        }

        if ($this->enableCsrfProtection) {
            /** @var Middleware $csrf */
            $csrf = $this->diContainer->get(CsrfProtection::class);
            $pipeline = new MiddlewarePipeline($csrf, $pipeline);
        }

        /** @var Middleware $localization */
        $localization = $this->diContainer->get(Localization::class);
        $pipeline = new MiddlewarePipeline($localization, $pipeline);

        /** @var Middleware $errorHandling */
        $errorHandling = $this->diContainer->get(ErrorHandling::class);
        $pipeline = new MiddlewarePipeline($errorHandling, $pipeline);

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
}
