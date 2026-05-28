<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\RefreshSignInSession;

final readonly class RefreshSignInSessionCommand
{
    public function __construct(
        public string $token,
    ) {}
}
