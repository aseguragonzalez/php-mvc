<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Middlewares;

use AlfonsoSG\Mvc\AuthSettings;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Responses\Headers\Location;
use AlfonsoSG\Mvc\Responses\Headers\SetCookie;
use AlfonsoSG\Mvc\Responses\StatusCode;
use AlfonsoSG\Mvc\Routes\AuthenticationRequiredException;
use AlfonsoSG\Mvc\Routes\RouteMethod;
use AlfonsoSG\Mvc\Routes\Router;
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
