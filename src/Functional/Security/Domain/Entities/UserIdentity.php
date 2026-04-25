<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Entities;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\InvalidCredentialsException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\UserBlockedException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\UserIsNotActiveException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\UsernameIsNotEmailException;
use AlfonsoSG\Mvc\Security\Identity;

final readonly class UserIdentity implements Identity
{
    /**
     * @param array<string> $roles
     */
    private function __construct(
        public string $passwordHash,
        public array $roles,
        private string $username,
        public bool $isActive = false,
        public bool $isAuthenticated = false,
        public bool $isBlocked = false
    ) {
        if (('anonymous' !== $this->username) && !filter_var($this->username, FILTER_VALIDATE_EMAIL)) {
            throw new UsernameIsNotEmailException($this->username);
        }
    }

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
        return in_array($role, $this->roles);
    }

    public function username(): string
    {
        return $this->username;
    }

    public function activate(): self
    {
        return new self(
            passwordHash: $this->passwordHash,
            roles: $this->roles,
            username: $this->username,
            isActive: true,
            isAuthenticated: $this->isAuthenticated,
            isBlocked: $this->isBlocked
        );
    }

    public function deactivate(): self
    {
        return new self(
            passwordHash: $this->passwordHash,
            roles: $this->roles,
            username: $this->username,
            isActive: false,
            isAuthenticated: $this->isAuthenticated,
            isBlocked: $this->isBlocked
        );
    }

    public function block(): self
    {
        return new self(
            passwordHash: $this->passwordHash,
            roles: $this->roles,
            username: $this->username,
            isActive: $this->isActive,
            isAuthenticated: $this->isAuthenticated,
            isBlocked: true
        );
    }

    public function unblock(): self
    {
        return new self(
            passwordHash: $this->passwordHash,
            roles: $this->roles,
            username: $this->username,
            isActive: $this->isActive,
            isAuthenticated: $this->isAuthenticated,
            isBlocked: false
        );
    }

    public function validatePassword(string $password): void
    {
        if (password_verify($password, $this->passwordHash)) {
            return;
        }

        throw new InvalidCredentialsException($this->username);
    }

    public function authenticate(string $password): UserIdentity
    {
        $this->validatePassword($password);

        if ($this->isAuthenticated) {
            return $this;
        }

        if (!$this->isActive) {
            throw new UserIsNotActiveException($this->username);
        }

        if ($this->isBlocked) {
            throw new UserBlockedException($this->username);
        }

        return new self(
            passwordHash: $this->passwordHash,
            roles: $this->roles,
            username: $this->username,
            isActive: $this->isActive,
            isAuthenticated: true,
            isBlocked: $this->isBlocked
        );
    }

    public function updatePassword(string $newPassword): self
    {
        if (!$this->isActive) {
            throw new UserIsNotActiveException($this->username);
        }

        if ($this->isBlocked) {
            throw new UserBlockedException($this->username);
        }

        return new self(
            passwordHash: password_hash($newPassword, PASSWORD_BCRYPT),
            roles: $this->roles,
            username: $this->username,
            isActive: $this->isActive,
            isAuthenticated: $this->isAuthenticated,
            isBlocked: $this->isBlocked
        );
    }

    /**
     * @param array<string> $newRoles
     */
    public function updateRoles(array $newRoles): self
    {
        return new self(
            passwordHash: $this->passwordHash,
            roles: $newRoles,
            username: $this->username,
            isActive: $this->isActive,
            isAuthenticated: $this->isAuthenticated,
            isBlocked: $this->isBlocked
        );
    }

    public function getCurrentIdentity(): Identity
    {
        return CurrentIdentity::build(
            isAuthenticated: $this->isAuthenticated,
            roles: $this->roles,
            username: $this->username,
        );
    }

    public static function anonymous(): self
    {
        return new self(
            passwordHash: '',
            roles: [],
            username: 'anonymous',
            isActive: false,
            isAuthenticated: false,
            isBlocked: false
        );
    }

    /**
     * @param array<string> $roles
     */
    public static function build(
        string $passwordHash,
        array $roles,
        string $username,
        bool $isActive,
        bool $isBlocked
    ): self {
        return new self(
            passwordHash: $passwordHash,
            roles: $roles,
            username: $username,
            isActive: $isActive,
            isAuthenticated: false,
            isBlocked: $isBlocked
        );
    }

    /**
     * @param array<string> $roles
     */
    public static function new(string $username, array $roles, string $password): self
    {
        return new self(
            passwordHash: password_hash($password, PASSWORD_BCRYPT),
            roles: $roles,
            username: $username,
            isActive: false,
            isAuthenticated: false,
            isBlocked: false
        );
    }
}
