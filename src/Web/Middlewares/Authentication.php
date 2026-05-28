<?php

declare(strict_types=1);

namespace PhpMvc\Middlewares;

use PhpMvc\AuthSettings;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Responses\Headers\Location;
use PhpMvc\Responses\Headers\SetCookie;
use PhpMvc\Responses\StatusCode;
use PhpMvc\Security\Domain\Exceptions\SessionExpiredException;
use PhpMvc\Security\IdentityManager;
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
