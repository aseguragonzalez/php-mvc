<?php

declare(strict_types=1);

namespace PhpMvc\Security\Domain\Exceptions;

final class SignUpChallengeException extends \Exception
{
    public function __construct(string $token = '')
    {
        parent::__construct("Invalid or expired sign-up token: {$token}");
    }
}
