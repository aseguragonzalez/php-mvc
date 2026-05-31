<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\ResetPasswordFromToken;

use PhpMvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromTokenCommand;
use PhpMvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromTokenHandler;
use PhpMvc\Security\Domain\Entities\ResetPasswordChallenge;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Exceptions\ResetPasswordChallengeException;
use PhpMvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ResetPasswordFromTokenTest extends TestCase
{
    public function testExecuteUpdatesPasswordWhenChallengeValid(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'old')->activate();
        $challenge = ResetPasswordChallenge::build(
            'token',
            new \DateTimeImmutable()->modify('+1 hour'),
            $user
        );

        $resetPasswordChallengeRepository = $this->createStub(ResetPasswordChallengeRepository::class);
        $resetPasswordChallengeRepository->method('getByToken')->willReturn($challenge);

        $userIdentityRepository = $this->createMock(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn($user);
        $userIdentityRepository->expects($this->once())->method('save');

        $handler = new ResetPasswordFromTokenHandler($resetPasswordChallengeRepository, $userIdentityRepository);

        $handler->execute(new ResetPasswordFromTokenCommand('token', 'newpass'));
    }

    public function testExecuteDoesNothingWhenChallengeNotFound(): void
    {
        $resetPasswordChallengeRepository = $this->createStub(ResetPasswordChallengeRepository::class);
        $resetPasswordChallengeRepository->method('getByToken')->willReturn(null);

        $userIdentityRepository = $this->createMock(UserIdentityRepository::class);
        $userIdentityRepository->expects($this->never())->method('save');

        $handler = new ResetPasswordFromTokenHandler($resetPasswordChallengeRepository, $userIdentityRepository);

        $handler->execute(new ResetPasswordFromTokenCommand('invalid-token', 'newpass'));
    }

    public function testExecuteDeletesAndThrowsWhenChallengeIsExpired(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'old')->activate();
        $expiredChallenge = ResetPasswordChallenge::build(
            'token',
            new \DateTimeImmutable()->modify('-1 hour'),
            $user
        );

        $resetPasswordChallengeRepository = $this->createMock(ResetPasswordChallengeRepository::class);
        $resetPasswordChallengeRepository->method('getByToken')->willReturn($expiredChallenge);
        $resetPasswordChallengeRepository->expects($this->once())->method('deleteByToken')->with('token');

        $userIdentityRepository = $this->createMock(UserIdentityRepository::class);
        $userIdentityRepository->expects($this->never())->method('save');

        $handler = new ResetPasswordFromTokenHandler($resetPasswordChallengeRepository, $userIdentityRepository);

        $this->expectException(ResetPasswordChallengeException::class);
        $handler->execute(new ResetPasswordFromTokenCommand('token', 'newpass'));
    }

    public function testExecuteDoesNothingWhenUserNotFound(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'old')->activate();
        $challenge = ResetPasswordChallenge::build(
            'token',
            new \DateTimeImmutable()->modify('+1 hour'),
            $user
        );

        $resetPasswordChallengeRepository = $this->createStub(ResetPasswordChallengeRepository::class);
        $resetPasswordChallengeRepository->method('getByToken')->willReturn($challenge);

        $userIdentityRepository = $this->createMock(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn(null);
        $userIdentityRepository->expects($this->never())->method('save');

        $handler = new ResetPasswordFromTokenHandler($resetPasswordChallengeRepository, $userIdentityRepository);

        $handler->execute(new ResetPasswordFromTokenCommand('token', 'newpass'));
    }
}
