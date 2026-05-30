<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\ActivateUserIdentity;

use PhpMvc\Security\Application\ActivateUserIdentity\ActivateUserIdentityCommand;
use PhpMvc\Security\Application\ActivateUserIdentity\ActivateUserIdentityHandler;
use PhpMvc\Security\Domain\Entities\SignUpChallenge;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Exceptions\SignUpChallengeException;
use PhpMvc\Security\Domain\Repositories\SignUpChallengeRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ActivateUserIdentityTest extends TestCase
{
    public function testExecuteActivatesUserAndDeletesChallenge(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'pass');
        $challenge = SignUpChallenge::build('token', new \DateTimeImmutable()->modify('+1 day'), $user);

        $signUpChallengeRepository = $this->createMock(SignUpChallengeRepository::class);
        $signUpChallengeRepository->method('getByToken')->willReturn($challenge);
        $signUpChallengeRepository->expects($this->once())->method('deleteByToken')->with('token');

        $userIdentityRepository = $this->createMock(UserIdentityRepository::class);
        $userIdentityRepository->expects($this->once())->method('save');

        $handler = new ActivateUserIdentityHandler($signUpChallengeRepository, $userIdentityRepository);

        $handler->execute(new ActivateUserIdentityCommand('token'));
    }

    public function testExecuteThrowsSignUpChallengeExceptionWhenChallengeNotFound(): void
    {
        $signUpChallengeRepository = $this->createStub(SignUpChallengeRepository::class);
        $signUpChallengeRepository->method('getByToken')->willReturn(null);

        $handler = new ActivateUserIdentityHandler(
            $signUpChallengeRepository,
            $this->createStub(UserIdentityRepository::class)
        );

        $this->expectException(SignUpChallengeException::class);
        $handler->execute(new ActivateUserIdentityCommand('token'));
    }

    public function testExecuteThrowsSignUpChallengeExceptionAndDeletesWhenChallengeExpired(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'pass');
        $challenge = SignUpChallenge::build('token', new \DateTimeImmutable()->modify('-1 day'), $user);

        $signUpChallengeRepository = $this->createMock(SignUpChallengeRepository::class);
        $signUpChallengeRepository->method('getByToken')->willReturn($challenge);
        $signUpChallengeRepository->expects($this->once())->method('deleteByToken')->with('token');

        $handler = new ActivateUserIdentityHandler(
            $signUpChallengeRepository,
            $this->createStub(UserIdentityRepository::class)
        );

        $this->expectException(SignUpChallengeException::class);
        $handler->execute(new ActivateUserIdentityCommand('token'));
    }
}
