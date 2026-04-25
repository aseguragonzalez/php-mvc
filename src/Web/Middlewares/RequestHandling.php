<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestHandling extends Middleware
{
    public function __construct(
        private readonly RequestHandlerInterface $requestHandler,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->requestHandler->handle($request);
    }
}
