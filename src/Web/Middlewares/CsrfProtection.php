<?php

declare(strict_types=1);

namespace PhpMvc\Middlewares;

use PhpMvc\Requests\RequestContext;
use PhpMvc\Responses\Headers\SetCookie;
use PhpMvc\Responses\StatusCode;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfProtection extends Middleware
{
    private const CONTEXT_KEY = 'csrf_token';

    /**
     * @param array<string> $protectedMethods
     */
    public function __construct(
        private readonly string $cookieName,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var ?RequestContext $context */
        $context = $request->getAttribute(RequestContext::class);
        if (null === $context) {
            throw new \RuntimeException('RequestContext not found in request attributes');
        }

        $method = strtoupper($request->getMethod());
        $token = $this->getOrCreateToken($request, $context);

        if (!in_array($method, $this->protectedMethods, true)) {
            return $handler->handle($request);
        }

        $requestToken = $this->getTokenFromRequest($request);
        if (!is_string($requestToken) || '' === $requestToken || !hash_equals($token, $requestToken)) {
            $response = $this->responseFactory->createResponse(StatusCode::Forbidden->value);
            $response->getBody()->write('Invalid CSRF token');

            return $response;
        }

        return $handler->handle($request);
    }

    public static function getTokenFromContext(RequestContext $context): string
    {
        // @var string $token
        return $context->get(self::CONTEXT_KEY);
    }

    private function getOrCreateToken(ServerRequestInterface $request, RequestContext $context): string
    {
        $cookies = $request->getCookieParams();
        $existing = $cookies[$this->cookieName] ?? null;
        if (is_string($existing) && '' !== $existing) {
            $context->set(self::CONTEXT_KEY, $existing);

            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $context->set(self::CONTEXT_KEY, $token);

        $setCookieHeader = SetCookie::createSecureCookie(
            cookieName: $this->cookieName,
            cookieValue: $token,
            expires: -1,
        );

        $this->responseFactory->createResponse(StatusCode::Ok->value)
            ->withAddedHeader($setCookieHeader->name, $setCookieHeader->value)
        ;

        return $token;
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['_csrf']) && is_string($parsedBody['_csrf'])) {
            return $parsedBody['_csrf'];
        }

        $headers = $request->getHeader('X-CSRF-Token');
        if (!empty($headers)) {
            // @var string $header
            return $headers[0];
        }

        return null;
    }
}
