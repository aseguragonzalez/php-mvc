<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Routes;

final class AuthenticationRequiredException extends \Exception
{
    public function __construct(Route $route)
    {
        parent::__construct("Authentication required for route: {$route}");
    }
}
