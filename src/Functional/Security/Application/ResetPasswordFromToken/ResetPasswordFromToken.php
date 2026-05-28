<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\ResetPasswordFromToken;

interface ResetPasswordFromToken
{
    public function execute(ResetPasswordFromTokenCommand $command): void;
}
