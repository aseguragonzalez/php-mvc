<?php

declare(strict_types=1);

namespace PhpMvc\Security\Domain\Services;

use PhpMvc\Security\Domain\Entities\ResetPasswordChallenge;
use PhpMvc\Security\Domain\Entities\SignUpChallenge;

interface ChallengeNotificator
{
    public function sendSignUpChallenge(string $email, SignUpChallenge $challenge): void;

    public function sendResetPasswordChallenge(string $email, ResetPasswordChallenge $challenge): void;
}
