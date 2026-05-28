<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\RequestResetPassword;

interface RequestResetPassword
{
    public function execute(RequestResetPasswordCommand $command): void;
}
