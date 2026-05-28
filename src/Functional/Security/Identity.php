<?php

declare(strict_types=1);

namespace PhpMvc\Security;

interface Identity
{
    public function isAuthenticated(): bool;

    /**
     * @return array<string>
     */
    public function getRoles(): array;

    public function hasRole(string $role): bool;

    public function username(): string;
}
