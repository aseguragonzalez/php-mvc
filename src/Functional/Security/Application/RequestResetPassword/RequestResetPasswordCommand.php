<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\RequestResetPassword;

final readonly class RequestResetPasswordCommand
{
    public function __construct(
        public string $username,
    ) {}
}
