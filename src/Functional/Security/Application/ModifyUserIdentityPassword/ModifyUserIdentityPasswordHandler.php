<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword;

use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\SessionExpiredException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\UserIsNotFoundException;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;

final readonly class ModifyUserIdentityPasswordHandler implements ModifyUserIdentityPassword
{
    public function __construct(
        private SignInSessionRepository $signInSessionRepository,
        private UserIdentityRepository $userIdentityRepository,
    ) {}

    public function execute(ModifyUserIdentityPasswordCommand $command): void
    {
        $session = $this->getSignInSessionOrFail($command->token);
        $user = $this->userIdentityRepository->getByUsername($session->identity->username());
        if (null === $user) {
            throw new UserIsNotFoundException($session->identity->username());
        }
        $user->validatePassword($command->currentPassword);
        $this->userIdentityRepository->save($user->updatePassword($command->newPassword));
    }

    private function getSignInSessionOrFail(string $token): SignInSession
    {
        $session = $this->signInSessionRepository->getByToken($token);
        if (null === $session) {
            throw new SessionExpiredException();
        }

        if ($session->isExpired()) {
            $this->signInSessionRepository->deleteByToken($token);

            throw new SessionExpiredException();
        }

        return $session;
    }
}
