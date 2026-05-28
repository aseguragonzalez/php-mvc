<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\SignOut;

final readonly class SignOutCommand
{
    public function __construct(
        public string $token,
    ) {}
}
