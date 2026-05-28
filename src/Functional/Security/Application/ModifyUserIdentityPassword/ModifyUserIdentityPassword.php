<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\ModifyUserIdentityPassword;

interface ModifyUserIdentityPassword
{
    public function execute(ModifyUserIdentityPasswordCommand $command): void;
}
