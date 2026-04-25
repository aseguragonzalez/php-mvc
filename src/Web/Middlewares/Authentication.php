<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Middlewares;

use AlfonsoSG\Mvc\AuthSettings;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Responses\Headers\Location;
use AlfonsoSG\Mvc\Responses\Headers\SetCookie;
use AlfonsoSG\Mvc\Responses\StatusCode;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\SessionExpiredException;
use AlfonsoSG\Mvc\Security\IdentityManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Authentication extends Middleware
{
    public function __construct(
        private readonly AuthSettings $settings,
        private readonly IdentityManager $identityManager,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string $token */
        $token = $request->getCookieParams()[$this->settings->cookieName] ?? '';

        try {
            $identity = $this->identityManager->getIdentity($token);

            /** @var RequestContext $context */
            $context = $request->getAttribute(RequestContext::class);
            $context->setIdentity($identity);
            $context->setIdentityToken($token);

            return $handler->handle($request);
        } catch (SessionExpiredException) {
            $setCookieHeader = SetCookie::removeCookie($this->settings->cookieName);
            $locationHeader = Location::toInternalUrl($this->settings->signInPath);

            return $this->responseFactory
                ->createResponse(StatusCode::SeeOther->value)
                ->withHeader($locationHeader->name, $locationHeader->value)
                ->withHeader($setCookieHeader->name, $setCookieHeader->value)
            ;
        }
    }
}
