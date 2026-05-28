<?php

declare(strict_types=1);

namespace PhpMvc\Middlewares;

use PhpMvc\Actions\Responses\View;
use PhpMvc\ErrorMapping;
use PhpMvc\ErrorSettings;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Views\ViewEngine;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandling extends Middleware
{
    public function __construct(
        private readonly ErrorSettings $settings,
        private readonly LoggerInterface $logger,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewEngine $viewEngine,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $exception) {
            $errorMapping = $this->settings->errorsMapping[get_class($exception)] ?? null;

            return null === $errorMapping
                ? $this->handleException($this->settings->errorsMappingDefaultValue, $request, $exception)
                : $this->handleException($errorMapping, $request, $exception);
        }
    }

    private function handleException(
        ErrorMapping $errorMapping,
        ServerRequestInterface $request,
        \Throwable $exception
    ): ResponseInterface {
        $this->logger->error('Error handling middleware: {message}', ['message' => $exception->getMessage()]);

        /** @var RequestContext $context */
        $context = $request->getAttribute(RequestContext::class);
        $responseBody = $this->viewEngine->render(new View($errorMapping->templateName, $errorMapping), $context);
        $response = $this->responseFactory->createResponse($errorMapping->statusCode);
        $response->getBody()->write($responseBody);

        return $response;
    }
}
