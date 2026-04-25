<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application\SignOut;

use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;

final readonly class SignOutHandler implements SignOut
{
    public function __construct(
        private SignInSessionRepository $signInSessionRepository,
    ) {}

    public function execute(SignOutCommand $command): void
    {
        $this->signInSessionRepository->deleteByToken($command->token);
    }
}
