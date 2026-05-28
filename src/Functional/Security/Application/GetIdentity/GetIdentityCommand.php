<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\GetIdentity;

final readonly class GetIdentityCommand
{
    public function __construct(
        public ?string $token,
    ) {}
}
