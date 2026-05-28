<?php

declare(strict_types=1);

namespace PhpMvc\Security\Domain\Entities;

use PhpMvc\Security\Challenge;
use PhpMvc\Security\Identity;

final readonly class SignInSession
{
    private function __construct(public Challenge $challenge, public Identity $identity) {}

    public function isExpired(): bool
    {
        return $this->challenge->isExpired();
    }

    public function refreshUntil(\DateTimeImmutable $dateTime): self
    {
        return new self($this->challenge->refreshUntil($dateTime), $this->identity);
    }

    public static function new(\DateTimeImmutable $expiredAt, Identity $identity): self
    {
        return new self(SignInChallenge::new($expiredAt), $identity);
    }

    public static function build(Challenge $challenge, Identity $identity): self
    {
        return new self($challenge, $identity);
    }
}
