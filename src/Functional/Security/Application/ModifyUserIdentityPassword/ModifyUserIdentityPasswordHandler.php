<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\ModifyUserIdentityPassword;

use PhpMvc\Security\Domain\Entities\SignInSession;
use PhpMvc\Security\Domain\Exceptions\SessionExpiredException;
use PhpMvc\Security\Domain\Exceptions\UserIsNotFoundException;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;

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
