<?php

declare(strict_types=1);

namespace PhpMvc\Routes;

final class RouteDoesNotFoundException extends \Exception
{
    public function __construct(RouteMethod $method, string $path)
    {
        parent::__construct("Route not found: {$method->value} {$path}");
    }
}
