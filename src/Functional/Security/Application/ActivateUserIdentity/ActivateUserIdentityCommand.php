<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\ActivateUserIdentity;

final readonly class ActivateUserIdentityCommand
{
    public function __construct(
        public string $token,
    ) {}
}
