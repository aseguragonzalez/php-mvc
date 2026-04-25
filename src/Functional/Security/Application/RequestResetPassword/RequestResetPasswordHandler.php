<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\RequestResetPassword;

use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Entities\ResetPasswordChallenge;
use AlfonsoSG\Mvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
use AlfonsoSG\Mvc\Security\Domain\Services\ChallengeNotificator;

final readonly class RequestResetPasswordHandler implements RequestResetPassword
{
    public function __construct(
        private UserIdentityRepository $userIdentityRepository,
        private ResetPasswordChallengeRepository $resetPasswordChallengeRepository,
        private ChallengeNotificator $notificator,
        private ChallengesExpirationTime $expirationTime,
    ) {}

    public function execute(RequestResetPasswordCommand $command): void
    {
        $user = $this->userIdentityRepository->getByUsername($command->username);
        if (null === $user) {
            return;
        }

        $challenge = ResetPasswordChallenge::new(
            $this->expiresAt($this->expirationTime->resetPasswordChallenge),
            $user
        );
        $this->resetPasswordChallengeRepository->save($challenge);
        $this->notificator->sendResetPasswordChallenge($command->username, $challenge);
    }

    private function expiresAt(int $minutes): \DateTimeImmutable
    {
        return new \DateTimeImmutable()->modify("+{$minutes} minutes");
    }
}
