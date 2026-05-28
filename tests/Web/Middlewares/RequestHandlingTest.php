<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Middlewares;

use PhpMvc\Middlewares\RequestHandling;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Support\Psr7\TestPsr17Factory;

/**
 * @internal
 *
 * @coversNothing
 */
class RequestHandlingTest extends TestCase
{
    private TestPsr17Factory $requestFactory;
    private RequestHandling $middleware;
    private MockObject&RequestHandlerInterface $requestHandlerMock;
    private RequestHandlerInterface&Stub $nextHandler;

    protected function setUp(): void
    {
        $this->requestFactory = new TestPsr17Factory();
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->nextHandler = $this->createStub(RequestHandlerInterface::class);
        $this->middleware = new RequestHandling(
            requestHandler: $this->requestHandlerMock,
        );
    }

    public function testHandleRequestReturnsResponse(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/any-uri');
        $expectedResponse = $this->requestFactory->createResponse(200);
        $this->requestHandlerMock->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse)
        ;

        $response = $this->middleware->process($request, $this->nextHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandleRequestPassesRequestToHandler(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/submit');
        $expectedResponse = $this->requestFactory->createResponse(201);
        $this->requestHandlerMock->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse)
        ;

        $response = $this->middleware->process($request, $this->nextHandler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testHandleRequestWithDifferentResponse(): void
    {
        $request = $this->requestFactory->createServerRequest('DELETE', '/delete');
        $expectedResponse = $this->requestFactory->createResponse(204);
        $this->requestHandlerMock->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse)
        ;

        $response = $this->middleware->process($request, $this->nextHandler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
