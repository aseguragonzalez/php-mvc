<?php

declare(strict_types=1);

namespace PhpMvc\Routes;

final class AccessDeniedException extends \Exception
{
    public function __construct(Route $route)
    {
        parent::__construct("Access denied for route: {$route}");
    }
}
