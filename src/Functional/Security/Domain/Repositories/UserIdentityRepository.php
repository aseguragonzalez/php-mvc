<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Repositories;

use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;

interface UserIdentityRepository
{
    public function save(UserIdentity $user): void;

    public function getByUsername(string $username): ?UserIdentity;

    public function existsByUsername(string $username): bool;
}
