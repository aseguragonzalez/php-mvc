<?php

declare(strict_types=1);

namespace PhpMvc\Middlewares;

use PhpMvc\AuthSettings;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Responses\Headers\Location;
use PhpMvc\Responses\Headers\SetCookie;
use PhpMvc\Responses\StatusCode;
use PhpMvc\Routes\AuthenticationRequiredException;
use PhpMvc\Routes\RouteMethod;
use PhpMvc\Routes\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Authorization extends Middleware
{
    public function __construct(
        private readonly AuthSettings $settings,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Router $router,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = RouteMethod::fromString($request->getMethod());
        $route = $this->router->get($method, $path);

        /** @var RequestContext $context */
        $context = $request->getAttribute(RequestContext::class);
        $identity = $context->getIdentity();

        try {
            $route->ensureAuthenticated($identity);
        } catch (AuthenticationRequiredException) {
            $setCookieHeader = SetCookie::removeCookie($this->settings->cookieName);
            $locationHeader = Location::toInternalUrl($this->settings->signInPath);

            return $this->responseFactory
                ->createResponse(StatusCode::SeeOther->value)
                ->withHeader($locationHeader->name, $locationHeader->value)
                ->withHeader($setCookieHeader->name, $setCookieHeader->value)
            ;
        }

        $route->ensureAuthorized($identity);

        return $handler->handle($request);
    }
}
