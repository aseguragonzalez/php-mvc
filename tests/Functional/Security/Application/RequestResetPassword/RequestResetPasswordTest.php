<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application\RequestResetPassword;

use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPasswordCommand;
use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPasswordHandler;
use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
use AlfonsoSG\Mvc\Security\Domain\Services\ChallengeNotificator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RequestResetPasswordTest extends TestCase
{
    public function testExecuteCreatesChallengeAndNotifiesWhenUserExists(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'pass');
        $userIdentityRepository = $this->createStub(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn($user);

        $resetPasswordChallengeRepository = $this->createMock(ResetPasswordChallengeRepository::class);
        $resetPasswordChallengeRepository->expects($this->once())->method('save');

        $notificator = $this->createMock(ChallengeNotificator::class);
        $notificator->expects($this->once())->method('sendResetPasswordChallenge');

        $handler = new RequestResetPasswordHandler(
            $userIdentityRepository,
            $resetPasswordChallengeRepository,
            $notificator,
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $handler->execute(new RequestResetPasswordCommand('user@example.com'));
    }
}
