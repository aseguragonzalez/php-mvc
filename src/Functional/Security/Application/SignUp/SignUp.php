<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\SignUp;

interface SignUp
{
    public function execute(SignUpCommand $command): void;
}
