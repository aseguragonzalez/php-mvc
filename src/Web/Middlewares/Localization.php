<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Middlewares;

use AlfonsoSG\Mvc\LanguageSettings;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Requests\RequestContextKeys;
use AlfonsoSG\Mvc\Responses\Headers\ContentLanguage;
use AlfonsoSG\Mvc\Responses\Headers\Location;
use AlfonsoSG\Mvc\Responses\Headers\SetCookie;
use AlfonsoSG\Mvc\Responses\StatusCode;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Localization extends Middleware
{
    public function __construct(
        private readonly LanguageSettings $settings,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException('RequestContext not found in request attributes');
        }

        if ($this->isSetLanguageCookieRequest($request)) {
            return $this->createSetLanguageResponse($request);
        }

        if ($this->hasValidLanguageCookie($request)) {
            return $this->handleRequestWithCurrentLanguage($handler, $context, $request);
        }

        return $this->handleRequestWithAcceptedOrDefaultLanguage($handler, $context, $request);
    }

    private function isSetLanguageCookieRequest(ServerRequestInterface $request): bool
    {
        $uri = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        return 'POST' === $method && $uri === $this->settings->setUrl;
    }

    private function createSetLanguageResponse(ServerRequestInterface $request): ResponseInterface
    {
        $language = $this->getLanguageFromBodyOrDefault($request->getParsedBody());
        $setCookieHeader = SetCookie::createSecureCookie(
            cookieName: $this->settings->cookieName,
            cookieValue: $language,
            expires: -1
        );
        $locationHeader = Location::toInternalUrl($request->getHeaderLine('Referer') ?: '/');
        $contentLanguageHeader = ContentLanguage::createFromCurrentLanguage($language);

        return $this->responseFactory
            ->createResponse(StatusCode::Found->value)
            ->withHeader($locationHeader->name, $locationHeader->value)
            ->withAddedHeader($contentLanguageHeader->name, $contentLanguageHeader->value)
            ->withAddedHeader($setCookieHeader->name, $setCookieHeader->value)
        ;
    }

    /**
     * @param null|array<mixed|string>|object $parsedBody
     */
    private function getLanguageFromBodyOrDefault(array|object|null $parsedBody): string
    {
        $language = is_array($parsedBody) && isset($parsedBody['language']) && is_string($parsedBody['language'])
            ? (string) $parsedBody['language']
            : null;

        return isset($language) && $this->isValidLanguage($language)
            ? $language
            : $this->settings->defaultValue;
    }

    private function isValidLanguage(?string $language): bool
    {
        return isset($language) && in_array($language, $this->settings->languages, true);
    }

    private function hasValidLanguageCookie(ServerRequestInterface $request): bool
    {
        $cookieParams = $request->getCookieParams();

        /** @var null|string $cookieValue */
        $cookieValue = $cookieParams[$this->settings->cookieName] ?? null;

        return $this->isValidLanguage($cookieValue);
    }

    private function handleRequestWithCurrentLanguage(
        RequestHandlerInterface $handler,
        RequestContext $context,
        ServerRequestInterface $request
    ): ResponseInterface {
        $cookieParams = $request->getCookieParams();

        /** @var string $language */
        $language = $cookieParams[$this->settings->cookieName];
        $context->set(RequestContextKeys::Language->value, $language);
        $contentLanguageHeader = ContentLanguage::createFromCurrentLanguage($language);

        return $handler->handle($request)
            ->withHeader($contentLanguageHeader->name, $contentLanguageHeader->value)
        ;
    }

    private function handleRequestWithAcceptedOrDefaultLanguage(
        RequestHandlerInterface $handler,
        RequestContext $context,
        ServerRequestInterface $request
    ): ResponseInterface {
        $language = $this->getLanguageFromRequestOrDefault($request);
        $setCookieHeader = SetCookie::createSecureCookie(
            cookieName: $this->settings->cookieName,
            cookieValue: $language,
            expires: -1
        );
        $contentLanguageHeader = ContentLanguage::createFromCurrentLanguage($language);
        $context->set(RequestContextKeys::Language->value, $language);

        return $handler->handle($request)
            ->withAddedHeader($contentLanguageHeader->name, $contentLanguageHeader->value)
            ->withAddedHeader($setCookieHeader->name, $setCookieHeader->value)
        ;
    }

    private function getLanguageFromRequestOrDefault(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine('Accept-Language');
        if (!$header) {
            return $this->settings->defaultValue;
        }

        $header = preg_replace('/^Accept-Language:\s*/i', '', $header);
        $parts = null !== $header ? explode(',', $header) : [];

        $languages = [];
        foreach ($parts as $part) {
            $lang = trim(explode(';', $part)[0]);
            if ('' !== $lang) {
                $languages[] = $lang;
            }
        }

        $filtered = array_values(array_intersect($languages, $this->settings->languages));

        return $filtered[0] ?? $this->settings->defaultValue;
    }
}
