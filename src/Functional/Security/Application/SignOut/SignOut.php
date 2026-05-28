<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\SignOut;

interface SignOut
{
    public function execute(SignOutCommand $command): void;
}
