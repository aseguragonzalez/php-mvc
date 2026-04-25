<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\RefreshSignInSession;

use AlfonsoSG\Mvc\Security\Challenge;

interface RefreshSignInSession
{
    public function execute(RefreshSignInSessionCommand $command): Challenge;
}
