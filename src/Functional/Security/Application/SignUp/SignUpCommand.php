<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\SignUp;

final readonly class SignUpCommand
{
    /**
     * @param array<string> $roles
     */
    public function __construct(
        public string $username,
        public string $password,
        public array $roles,
    ) {}
}
