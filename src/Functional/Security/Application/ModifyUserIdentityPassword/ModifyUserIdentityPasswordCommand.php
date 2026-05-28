<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\ModifyUserIdentityPassword;

final readonly class ModifyUserIdentityPasswordCommand
{
    public function __construct(
        public string $token,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
