<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Repositories;

use AlfonsoSG\Mvc\Security\Domain\Entities\SignUpChallenge;

interface SignUpChallengeRepository
{
    public function save(SignUpChallenge $challenge): void;

    public function getByToken(string $token): ?SignUpChallenge;

    public function deleteByToken(string $token): void;
}
