<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Entities;

final class SignInChallenge extends BaseChallenge
{
    private function __construct(string $token, \DateTimeImmutable $expiresAt)
    {
        parent::__construct($token, $expiresAt);
    }

    public static function new(\DateTimeImmutable $expiresAt): self
    {
        return new self((string) bin2hex(random_bytes(16)), $expiresAt);
    }

    public static function build(string $token, \DateTimeImmutable $expiresAt): self
    {
        return new self($token, $expiresAt);
    }

    public function refreshUntil(\DateTimeImmutable $dateTime): self
    {
        return self::build($this->token, $dateTime);
    }
}
