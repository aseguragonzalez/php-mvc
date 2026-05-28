<?php

declare(strict_types=1);

namespace PhpMvc\Security\Domain\Entities;

final class SignUpChallenge extends BaseChallenge
{
    private function __construct(
        string $token,
        \DateTimeImmutable $expiresAt,
        public readonly UserIdentity $userIdentity
    ) {
        parent::__construct($token, $expiresAt);
    }

    public static function new(\DateTimeImmutable $expiresAt, UserIdentity $userIdentity): self
    {
        return new self((string) bin2hex(random_bytes(16)), $expiresAt, $userIdentity);
    }

    public static function build(string $token, \DateTimeImmutable $expiresAt, UserIdentity $userIdentity): self
    {
        return new self($token, $expiresAt, $userIdentity);
    }

    public function refreshUntil(\DateTimeImmutable $dateTime): self
    {
        return self::build($this->token, $dateTime, $this->userIdentity);
    }
}
