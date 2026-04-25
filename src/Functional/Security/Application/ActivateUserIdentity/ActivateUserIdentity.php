<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity;

interface ActivateUserIdentity
{
    public function execute(ActivateUserIdentityCommand $command): void;
}
