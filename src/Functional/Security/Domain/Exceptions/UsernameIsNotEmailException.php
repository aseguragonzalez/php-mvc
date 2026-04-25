<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Exceptions;

final class UsernameIsNotEmailException extends \Exception
{
    public function __construct(string $username = '')
    {
        parent::__construct("Username is not a valid email address: {$username}.");
    }
}
