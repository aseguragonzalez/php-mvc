<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Domain\Services;

use AlfonsoSG\Mvc\Security\Domain\Entities\ResetPasswordChallenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignUpChallenge;

interface ChallengeNotificator
{
    public function sendSignUpChallenge(string $email, SignUpChallenge $challenge): void;

    public function sendResetPasswordChallenge(string $email, ResetPasswordChallenge $challenge): void;
}
