<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Middlewares;

use PhpMvc\ErrorMapping;
use PhpMvc\ErrorSettings;
use PhpMvc\Middlewares\ErrorHandling;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Responses\StatusCode;
use PhpMvc\Views\ViewEngine;
use Tests\Support\Psr7\TestPsr17Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class ErrorHandlingTest extends TestCase
{
    private ErrorHandling $middleware;
    private ErrorSettings $settings;
    private LoggerInterface&MockObject $logger;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $psrFactory = new TestPsr17Factory();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->settings = new ErrorSettings(
            errorsMapping: [\InvalidArgumentException::class => new ErrorMapping(400, 'custom_error', 'custom_error')],
            errorsMappingDefaultValue: new ErrorMapping(500, 'error', 'error')
        );
        $this->request = $psrFactory
            ->createServerRequest('GET', '/')
            ->withAttribute(RequestContext::class, new RequestContext())
        ;
        $this->middleware = new ErrorHandling(
            settings: $this->settings,
            logger: $this->logger,
            responseFactory: $psrFactory,
            viewEngine: $this->createStub(ViewEngine::class)
        );
    }

    public function testHandlesExceptionWithCustomMapping(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Error handling middleware: {message}', ['message' => 'Test'])
        ;
        $next = $this->createStub(RequestHandlerInterface::class);
        $next->method('handle')->willThrowException(new \InvalidArgumentException('Test'));

        $response = $this->middleware->process($this->request, $next);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::BadRequest->value, $response->getStatusCode());
    }

    public function testHandlesExceptionWithDefaultMapping(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Error handling middleware: {message}', ['message' => 'Test'])
        ;
        $next = $this->createStub(RequestHandlerInterface::class);
        $next->method('handle')->willThrowException(new \Exception('Test'));

        $response = $this->middleware->process($this->request, $next);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCode::InternalServerError->value, $response->getStatusCode());
    }
}
