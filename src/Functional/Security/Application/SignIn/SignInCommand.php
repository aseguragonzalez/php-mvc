<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\SignIn;

final readonly class SignInCommand
{
    public function __construct(
        public string $username,
        public string $password,
        public bool $keepMeSignedIn,
    ) {}
}
