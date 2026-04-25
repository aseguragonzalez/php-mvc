<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\SignIn;

use AlfonsoSG\Mvc\Security\Challenge;

interface SignIn
{
    public function execute(SignInCommand $command): Challenge;
}
