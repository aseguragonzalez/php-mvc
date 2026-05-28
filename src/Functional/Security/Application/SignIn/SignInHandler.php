<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application\SignIn;

use PhpMvc\Security\Challenge;
use PhpMvc\Security\ChallengesExpirationTime;
use PhpMvc\Security\Domain\Entities\SignInSession;
use PhpMvc\Security\Domain\Exceptions\InvalidCredentialsException;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;

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
