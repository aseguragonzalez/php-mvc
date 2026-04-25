<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security;

interface Challenge
{
    public function isExpired(): bool;

    public function getToken(): string;

    public function getExpiresAt(): \DateTimeImmutable;

    public function refreshUntil(\DateTimeImmutable $dateTime): Challenge;
}
