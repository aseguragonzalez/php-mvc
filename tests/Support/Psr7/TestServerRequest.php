<?php

declare(strict_types=1);

namespace Tests\Support\Psr7;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class TestServerRequest implements ServerRequestInterface
{
    /** @var array<string, list<string>> */
    private array $headers = [];
    private string $protocolVersion = '1.1';
    private StreamInterface $body;

    /** @var array<string, mixed> */
    private array $serverParams;

    /** @var array<string, string> */
    private array $cookieParams = [];

    /** @var array<string, mixed> */
    private array $queryParams = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var null|array<string, mixed>|object */
    private array|object|null $parsedBody = null;

    /** @var array<mixed> */
    private array $uploadedFiles = [];
    private UriInterface $uri;

    /**
     * @param array<string, mixed> $serverParams
     */
    public function __construct(
        private string $method,
        string|UriInterface $uri,
        array $serverParams = [],
    ) {
        $this->uri = is_string($uri) ? new TestUri($uri) : $uri;
        $this->serverParams = $serverParams;
        $this->body = new TestStream();
    }

    public function getRequestTarget(): string
    {
        $target = $this->uri->getPath() ?: '/';
        $query = $this->uri->getQuery();
        if ('' !== $query) {
            $target .= '?'.$query;
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        return clone $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        return $clone;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /** @return array<string, list<string>> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /** @return list<string> */
    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = array_values((array) $value);

        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $clone = clone $this;
        $key = strtolower($name);
        $existing = $clone->headers[$key] ?? [];
        $clone->headers[$key] = array_merge($existing, array_values((array) $value));

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /** @return array<string, string> */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /** @param array<string, string> $cookies */
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /** @param array<string, mixed> $query */
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /** @return array<mixed> */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /** @param array<mixed> $uploadedFiles */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /** @return null|array<string, mixed>|object */
    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    /** @param null|array<string, mixed>|object $data */
    public function withParsedBody($data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
