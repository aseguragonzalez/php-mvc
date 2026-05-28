<?php

declare(strict_types=1);

namespace PhpMvc\Routes;

final class InvalidAction extends \Exception
{
    public function __construct(string $controller, string $action)
    {
        parent::__construct("Action '{$action}' is not a valid action for controller {$controller}");
    }
}
