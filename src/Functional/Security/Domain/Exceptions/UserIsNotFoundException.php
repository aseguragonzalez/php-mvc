<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Exceptions;

final class UserIsNotFoundException extends \Exception
{
    public function __construct(string $username = '')
    {
        parent::__construct("User is not found: {$username}.");
    }
}
