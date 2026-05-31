<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Middlewares;

use PhpMvc\Middlewares\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class MiddlewarePipelineTest extends TestCase
{
    public function testHandleDelegatesToMiddlewareProcess(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $next = $this->createStub(RequestHandlerInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($request, $next)
            ->willReturn($response)
        ;

        $pipeline = new MiddlewarePipeline($middleware, $next);

        $result = $pipeline->handle($request);

        $this->assertSame($response, $result);
    }

    public function testHandleReturnsResponseFromMiddleware(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $next = $this->createStub(RequestHandlerInterface::class);

        $expectedResponse = $this->createStub(ResponseInterface::class);
        $middleware = $this->createStub(MiddlewareInterface::class);
        $middleware->method('process')->willReturn($expectedResponse);

        $pipeline = new MiddlewarePipeline($middleware, $next);

        $this->assertSame($expectedResponse, $pipeline->handle($request));
    }
}
