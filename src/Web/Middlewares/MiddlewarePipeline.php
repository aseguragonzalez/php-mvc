<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class MiddlewarePipeline implements RequestHandlerInterface
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $next,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->next);
    }
}
