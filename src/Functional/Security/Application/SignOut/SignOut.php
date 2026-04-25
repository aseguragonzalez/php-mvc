<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\SignOut;

interface SignOut
{
    public function execute(SignOutCommand $command): void;
}
