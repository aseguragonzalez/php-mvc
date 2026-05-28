<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\ActivateUserIdentity;

use PhpMvc\Security\Domain\Exceptions\SignUpChallengeException;
use PhpMvc\Security\Domain\Repositories\SignUpChallengeRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;

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
