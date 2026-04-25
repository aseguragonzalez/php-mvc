<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\SignUpChallengeException;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignUpChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;

final readonly class ActivateUserIdentityHandler implements ActivateUserIdentity
{
    public function __construct(
        private SignUpChallengeRepository $signUpChallengeRepository,
        private UserIdentityRepository $userIdentityRepository,
    ) {}

    public function execute(ActivateUserIdentityCommand $command): void
    {
        $challenge = $this->signUpChallengeRepository->getByToken($command->token);
        if (null === $challenge) {
            throw new SignUpChallengeException($command->token);
        }

        if ($challenge->isExpired()) {
            $this->signUpChallengeRepository->deleteByToken($command->token);

            throw new SignUpChallengeException($command->token);
        }

        $this->userIdentityRepository->save($challenge->userIdentity->activate());
        $this->signUpChallengeRepository->deleteByToken($command->token);
    }
}
