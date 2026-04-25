<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\GetIdentity;

use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\SessionExpiredException;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use AlfonsoSG\Mvc\Security\Identity;

final readonly class GetIdentityHandler implements GetIdentity
{
    public function __construct(
        private SignInSessionRepository $signInSessionRepository,
    ) {}

    public function execute(GetIdentityCommand $command): Identity
    {
        if (!isset($command->token) || empty($command->token) || empty(trim($command->token))) {
            return UserIdentity::anonymous();
        }

        $session = $this->getSignInSessionOrFail($command->token);

        return $session->identity;
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
