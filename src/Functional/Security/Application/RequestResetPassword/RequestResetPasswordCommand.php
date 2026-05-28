<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\RequestResetPassword;

final readonly class RequestResetPasswordCommand
{
    public function __construct(
        public string $username,
    ) {}
}
