<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\SignIn;

use PhpMvc\Security\Challenge;

interface SignIn
{
    public function execute(SignInCommand $command): Challenge;
}
