<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\RequestResetPassword;

use PhpMvc\Security\Application\RequestResetPassword\RequestResetPasswordCommand;
use PhpMvc\Security\Application\RequestResetPassword\RequestResetPasswordHandler;
use PhpMvc\Security\ChallengesExpirationTime;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;
use PhpMvc\Security\Domain\Services\ChallengeNotificator;
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

    public function testExecuteDoesNothingWhenUserNotFound(): void
    {
        $userIdentityRepository = $this->createStub(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn(null);

        $resetPasswordChallengeRepository = $this->createMock(ResetPasswordChallengeRepository::class);
        $resetPasswordChallengeRepository->expects($this->never())->method('save');

        $notificator = $this->createMock(ChallengeNotificator::class);
        $notificator->expects($this->never())->method('sendResetPasswordChallenge');

        $handler = new RequestResetPasswordHandler(
            $userIdentityRepository,
            $resetPasswordChallengeRepository,
            $notificator,
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $handler->execute(new RequestResetPasswordCommand('unknown@example.com'));
    }
}
