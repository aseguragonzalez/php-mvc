<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Entities;

use AlfonsoSG\Mvc\Security\Challenge;

final class ResetPasswordChallenge extends BaseChallenge
{
    private function __construct(
        \DateTimeImmutable $expiresAt,
        string $token,
        public readonly UserIdentity $userIdentity
    ) {
        parent::__construct($token, $expiresAt);
    }

    public static function new(\DateTimeImmutable $expiresAt, UserIdentity $userIdentity): self
    {
        return new self($expiresAt, (string) bin2hex(random_bytes(16)), $userIdentity);
    }

    public static function build(string $token, \DateTimeImmutable $expiresAt, UserIdentity $userIdentity): self
    {
        return new self($expiresAt, $token, $userIdentity);
    }

    public function refreshUntil(\DateTimeImmutable $dateTime): Challenge
    {
        return self::build($this->token, $dateTime, $this->userIdentity);
    }
}
