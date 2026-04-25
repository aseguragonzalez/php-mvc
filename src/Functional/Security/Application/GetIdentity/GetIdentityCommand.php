<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\GetIdentity;

final readonly class GetIdentityCommand
{
    public function __construct(
        public ?string $token,
    ) {}
}
