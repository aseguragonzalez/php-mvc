<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken;

final readonly class ResetPasswordFromTokenCommand
{
    public function __construct(
        public string $token,
        public string $newPassword,
    ) {}
}
