<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\SignIn;

use PhpMvc\Security\Application\SignIn\SignInCommand;
use PhpMvc\Security\Application\SignIn\SignInHandler;
use PhpMvc\Security\ChallengesExpirationTime;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Exceptions\InvalidCredentialsException;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignInTest extends TestCase
{
    public function testExecuteReturnsChallengeWhenCredentialsValid(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'password')->activate();
        $userIdentityRepository = $this->createStub(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn($user);

        $signInSessionRepository = $this->createMock(SignInSessionRepository::class);
        $signInSessionRepository->expects($this->once())->method('save');

        $handler = new SignInHandler(
            $userIdentityRepository,
            $signInSessionRepository,
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $challenge = $handler->execute(new SignInCommand('user@example.com', 'password', false));

        $this->assertNotEmpty($challenge->getToken());
    }

    public function testExecuteThrowsInvalidCredentialsWhenUserNotFound(): void
    {
        $userIdentityRepository = $this->createStub(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn(null);

        $handler = new SignInHandler(
            $userIdentityRepository,
            $this->createStub(SignInSessionRepository::class),
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $this->expectException(InvalidCredentialsException::class);
        $handler->execute(new SignInCommand('unknown@example.com', 'password', false));
    }

    public function testExecuteUsesRememberMeExpirationWhenKeepMeSignedIn(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'password')->activate();
        $userIdentityRepository = $this->createStub(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn($user);

        $signInSessionRepository = $this->createMock(SignInSessionRepository::class);
        $signInSessionRepository->expects($this->once())->method('save');

        $handler = new SignInHandler(
            $userIdentityRepository,
            $signInSessionRepository,
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $challenge = $handler->execute(new SignInCommand('user@example.com', 'password', true));

        $this->assertNotEmpty($challenge->getToken());
    }
}
