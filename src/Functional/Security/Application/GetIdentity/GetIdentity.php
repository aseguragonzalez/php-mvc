<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\GetIdentity;

use AlfonsoSG\Mvc\Security\Identity;

interface GetIdentity
{
    public function execute(GetIdentityCommand $command): Identity;
}
