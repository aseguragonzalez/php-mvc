<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Entities;

use AlfonsoSG\Mvc\Security\Identity;

final readonly class CurrentIdentity implements Identity
{
    /**
     * @param array<string> $roles
     */
    private function __construct(
        public bool $isAuthenticated,
        public array $roles,
        public string $username,
    ) {}

    public function isAuthenticated(): bool
    {
        return $this->isAuthenticated;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function username(): string
    {
        return $this->username;
    }

    /**
     * @param array<string> $roles
     */
    public static function build(
        bool $isAuthenticated,
        array $roles,
        string $username
    ): CurrentIdentity {
        return new self(
            isAuthenticated: $isAuthenticated,
            roles: $roles,
            username: $username
        );
    }
}
