<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\RefreshSignInSession;

use PhpMvc\Security\Challenge;

interface RefreshSignInSession
{
    public function execute(RefreshSignInSessionCommand $command): Challenge;
}
