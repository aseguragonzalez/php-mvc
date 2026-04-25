<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Actions\Responses;

use AlfonsoSG\Mvc\Controllers\Controller;
use AlfonsoSG\Mvc\Responses\Headers\ContentType;
use AlfonsoSG\Mvc\Responses\Headers\Header;
use AlfonsoSG\Mvc\Responses\StatusCode;
use Psr\Http\Message\ServerRequestInterface;

final class LocalRedirectTo extends ActionResponse
{
    /**
     * @param class-string  $controller
     * @param array<Header> $headers
     */
    private function __construct(
        public readonly string $action,
        public readonly string $controller,
        public readonly ?object $args = null,
        array $headers = [],
    ) {
        parent::__construct(data: new \stdClass(), headers: $headers, statusCode: StatusCode::SeeOther);
    }

    /**
     * @param class-string  $controller
     * @param array<Header> $headers
     */
    public static function create(
        string $action,
        string $controller,
        ?object $args = null,
        array $headers = [],
    ): self {
        if (!is_subclass_of($controller, Controller::class)) {
            throw new \InvalidArgumentException("Controller does not exist: {$controller}");
        }

        if (!method_exists($controller, $action)) {
            throw new \InvalidArgumentException("Action not found: {$action}");
        }

        $actionMethod = new \ReflectionMethod($controller, $action);
        $requireArguments = $actionMethod->getNumberOfRequiredParameters() > 0;
        if ($requireArguments && is_null($args)) {
            throw new \InvalidArgumentException("Action parameters for {$action} are required");
        }

        if ($requireArguments && false === LocalRedirectTo::checkActionArgs($actionMethod, $args)) {
            throw new \InvalidArgumentException("Action parameters for {$action} do not match");
        }

        if (empty(array_filter($headers, fn (Header $header) => true === $header instanceof ContentType))) {
            $headers[] = ContentType::html();
        }

        return new self($action, $controller, $args, $headers);
    }

    private static function checkActionArgs(\ReflectionMethod $actionMethod, object $args): bool
    {
        $argsProperties = get_object_vars($args);
        $requiredActionParameters = array_filter(
            $actionMethod->getParameters(),
            fn (\ReflectionParameter $param) => false === $param->isOptional()
                && false === $param->allowsNull()
                && ($type = $param->getType()) instanceof \ReflectionNamedType
                && ServerRequestInterface::class !== $type->getName()
        );

        foreach ($requiredActionParameters as $parameter) {
            if (!array_key_exists($parameter->getName(), $argsProperties)) {
                return false;
            }
        }

        return true;
    }
}
