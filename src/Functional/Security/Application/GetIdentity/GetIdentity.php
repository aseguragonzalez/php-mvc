<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\GetIdentity;

use PhpMvc\Security\Identity;

interface GetIdentity
{
    public function execute(GetIdentityCommand $command): Identity;
}
