<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\SignUp;

use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignUpChallenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignUpChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
use AlfonsoSG\Mvc\Security\Domain\Services\ChallengeNotificator;

final readonly class SignUpHandler implements SignUp
{
    public function __construct(
        private UserIdentityRepository $userIdentityRepository,
        private SignUpChallengeRepository $signUpChallengeRepository,
        private ChallengeNotificator $notificator,
        private ChallengesExpirationTime $expirationTime,
    ) {}

    public function execute(SignUpCommand $command): void
    {
        if ($this->userIdentityRepository->existsByUsername($command->username)) {
            return;
        }

        $user = UserIdentity::new($command->username, $command->roles, $command->password);
        $this->userIdentityRepository->save($user);
        $challenge = SignUpChallenge::new($this->expiresAt($this->expirationTime->signUp), $user);
        $this->signUpChallengeRepository->save($challenge);
        $this->notificator->sendSignUpChallenge($command->username, $challenge);
    }

    private function expiresAt(int $minutes): \DateTimeImmutable
    {
        return new \DateTimeImmutable()->modify("+{$minutes} minutes");
    }
}
