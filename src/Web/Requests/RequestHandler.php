<?php

declare(strict_types=1);

namespace PhpMvc\Requests;

use PhpMvc\Actions\ActionParameterBuilder;
use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Actions\Responses\LocalRedirectTo;
use PhpMvc\Actions\Responses\RedirectTo;
use PhpMvc\Actions\Responses\View;
use PhpMvc\Responses\StatusCode;
use PhpMvc\Routes\Route;
use PhpMvc\Routes\RouteMethod;
use PhpMvc\Routes\Router;
use PhpMvc\Views\ViewEngine;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ActionParameterBuilder $actionParameterBuilder,
        private readonly ContainerInterface $container,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Router $router,
        private readonly ViewEngine $viewEngine,
        private readonly string $defaultHost = '',
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->getRouteFromRequest($request);
        $args = $this->getArgsFromRequest($request, $route);
        $actionResponse = $this->executeAction($route, $args);

        return $this->createResponseFromActionResponse($request, $actionResponse);
    }

    private function getRouteFromRequest(ServerRequestInterface $request): Route
    {
        $path = $request->getUri()->getPath();
        $method = RouteMethod::fromString($request->getMethod());

        return $this->router->get($method, $path);
    }

    /**
     * @return array<mixed>
     */
    private function getArgsFromRequest(ServerRequestInterface $request, Route $route): array
    {
        /** @var array<string, float|int|string> $args */
        $args = array_merge($request->getQueryParams(), $route->getArgs($request->getUri()->getPath()));
        if (RouteMethod::Post === $route->method) {
            $parsedBody = $request->getParsedBody();
            if (!is_null($parsedBody) && is_array($parsedBody)) {
                /** @var array<string, float|int|string> $args */
                $args = array_merge($args, (array) $parsedBody);
            }
        }
        $action = new \ReflectionMethod($route->controller, $route->action);
        $this->actionParameterBuilder->withArgs($args);

        return array_map(
            function (\ReflectionParameter $param) use ($args, $request): mixed {
                /** @var ?\ReflectionNamedType $paramType */
                $paramType = $param->getType();
                if (null === $paramType) {
                    return null;
                }

                /** @var class-string $requestType */
                $requestType = $paramType->getName();
                // If the parameter type is ServerRequestInterface, return the request object
                if (ServerRequestInterface::class === $requestType) {
                    return $request;
                }
                $name = $param->getName();
                $value = $this->getValueOrDefault($name, $args, $param);

                return match ($paramType->getName()) {
                    'int' => InputNormalizer::toInt($value),
                    'float' => InputNormalizer::toFloat($value),
                    'string' => InputNormalizer::toString($value),
                    default => $this->actionParameterBuilder->build($requestType),
                };
            },
            $action->getParameters()
        );
    }

    /**
     * @param array<string, float|int|string> $args
     */
    private function getValueOrDefault(
        string $name,
        array $args,
        \ReflectionParameter $param
    ): mixed {
        return !array_key_exists($name, $args)
            ? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null)
            : $args[$name];
    }

    /**
     * @param array<mixed> $args
     */
    private function executeAction(Route $route, array $args): ActionResponse
    {
        $controller = $this->container->get($route->controller);
        $actionResponse = (0 === count($args))
            ? $controller->{$route->action}()
            : $controller->{$route->action}(...$args);

        if ($actionResponse instanceof ActionResponse) {
            return $actionResponse;
        }

        throw new \RuntimeException('Invalid Response object returned from controller');
    }

    private function createResponseFromActionResponse(
        ServerRequestInterface $request,
        ActionResponse $actionResponse
    ): ResponseInterface {
        return match (true) {
            $actionResponse instanceof LocalRedirectTo => $this->handleLocalRedirectTo($request, $actionResponse),
            $actionResponse instanceof RedirectTo => $this->handleRedirectTo($request, $actionResponse),
            $actionResponse instanceof View => $this->handleView($request, $actionResponse),
            default => throw new \RuntimeException('Invalid ActionResponse type'),
        };
    }

    private function handleLocalRedirectTo(
        ServerRequestInterface $request,
        LocalRedirectTo $actionResponse
    ): ResponseInterface {
        $host = empty($request->getHeaderLine('origin')) ? $this->defaultHost : $request->getHeaderLine('origin');
        $newRoute = $this->router->getFromControllerAndAction($actionResponse->controller, $actionResponse->action);
        if (is_null($newRoute)) {
            throw new \RuntimeException(
                "Route not found for controller: {$actionResponse->controller}, action: {$actionResponse->action}"
            );
        }

        /** @var array<string, null|float|int|string> $args */
        $args = array_map(
            fn ($v) => is_scalar($v) ? (string) $v : (null === $v ? null : ''),
            is_object($actionResponse->args) ? get_object_vars($actionResponse->args) : (array) $actionResponse->args
        );
        $path = $newRoute->getPathFromArgs($args);
        $response = $this->responseFactory
            ->createResponse(code: StatusCode::SeeOther->value)
            ->withHeader('Location', "{$host}{$path}")
        ;
        foreach ($actionResponse->headers as $header) {
            $response = $response->withAddedHeader($header->name, $header->value);
        }

        return $response;
    }

    private function handleRedirectTo(ServerRequestInterface $request, RedirectTo $actionResponse): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($actionResponse->statusCode->value);
        foreach ($actionResponse->headers as $header) {
            $response = $response->withAddedHeader($header->name, $header->value);
        }

        return $response;
    }

    private function handleView(ServerRequestInterface $request, View $actionResponse): ResponseInterface
    {
        /** @var RequestContext $context */
        $context = $request->getAttribute(RequestContext::class);
        $responseBody = $this->viewEngine->render($actionResponse, $context);
        $response = $this->responseFactory->createResponse($actionResponse->statusCode->value);
        foreach ($actionResponse->headers as $header) {
            $response = $response->withAddedHeader($header->name, $header->value);
        }
        $response->getBody()->write($responseBody);

        return $response;
    }
}
