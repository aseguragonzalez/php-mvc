<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\SignIn;

use AlfonsoSG\Mvc\Security\Challenge;
use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\InvalidCredentialsException;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;

final readonly class SignInHandler implements SignIn
{
    public function __construct(
        private UserIdentityRepository $userIdentityRepository,
        private SignInSessionRepository $signInSessionRepository,
        private ChallengesExpirationTime $expirationTime,
    ) {}

    public function execute(SignInCommand $command): Challenge
    {
        $user = $this->userIdentityRepository->getByUsername($command->username);
        if (null === $user) {
            throw new InvalidCredentialsException($command->username);
        }

        $authenticatedUser = $user->authenticate($command->password);

        $expiresAt = $command->keepMeSignedIn
            ? $this->expiresAt($this->expirationTime->signInWithRememberMe)
            : $this->expiresAt($this->expirationTime->signIn);

        $session = SignInSession::new($expiresAt, $authenticatedUser);
        $this->signInSessionRepository->save($session);

        return $session->challenge;
    }

    private function expiresAt(int $minutes): \DateTimeImmutable
    {
        return new \DateTimeImmutable()->modify("+{$minutes} minutes");
    }
}
