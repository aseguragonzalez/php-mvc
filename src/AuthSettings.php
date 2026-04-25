<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc;

final readonly class AuthSettings
{
    public function __construct(
        public string $signInPath,
        public string $signOutPath,
        public string $cookieName = 'auth',
    ) {}
}
