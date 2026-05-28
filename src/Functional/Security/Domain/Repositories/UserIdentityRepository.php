<?php

declare(strict_types=1);

namespace PhpMvc\Security\Domain\Repositories;

use PhpMvc\Security\Domain\Entities\UserIdentity;

interface UserIdentityRepository
{
    public function save(UserIdentity $user): void;

    public function getByUsername(string $username): ?UserIdentity;

    public function existsByUsername(string $username): bool;
}
