<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\ResetPasswordChallengeException;
use AlfonsoSG\Mvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;

final readonly class ResetPasswordFromTokenHandler implements ResetPasswordFromToken
{
    public function __construct(
        private ResetPasswordChallengeRepository $resetPasswordChallengeRepository,
        private UserIdentityRepository $userIdentityRepository,
    ) {}

    public function execute(ResetPasswordFromTokenCommand $command): void
    {
        $resetPasswordChallenge = $this->resetPasswordChallengeRepository->getByToken($command->token);
        if (null === $resetPasswordChallenge) {
            return;
        }

        if ($resetPasswordChallenge->isExpired()) {
            $this->resetPasswordChallengeRepository->deleteByToken($command->token);

            throw new ResetPasswordChallengeException($command->token);
        }

        $user = $this->userIdentityRepository->getByUsername($resetPasswordChallenge->userIdentity->username());
        if (null === $user) {
            return;
        }

        $this->userIdentityRepository->save($user->updatePassword($command->newPassword));
    }
}
