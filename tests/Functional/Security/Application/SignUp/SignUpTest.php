<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application\SignUp;

use AlfonsoSG\Mvc\Security\Application\SignUp\SignUpCommand;
use AlfonsoSG\Mvc\Security\Application\SignUp\SignUpHandler;
use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignUpChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
use AlfonsoSG\Mvc\Security\Domain\Services\ChallengeNotificator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignUpTest extends TestCase
{
    public function testExecuteCreatesUserAndSendsChallengeWhenUserDoesNotExist(): void
    {
        $userIdentityRepository = $this->createStub(UserIdentityRepository::class);
        $userIdentityRepository->method('existsByUsername')->willReturn(false);

        $signUpChallengeRepository = $this->createMock(SignUpChallengeRepository::class);
        $signUpChallengeRepository->expects($this->once())->method('save');

        $notificator = $this->createMock(ChallengeNotificator::class);
        $notificator->expects($this->once())->method('sendSignUpChallenge');

        $handler = new SignUpHandler(
            $userIdentityRepository,
            $signUpChallengeRepository,
            $notificator,
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $handler->execute(new SignUpCommand('user@example.com', 'password', ['admin']));
    }
}
