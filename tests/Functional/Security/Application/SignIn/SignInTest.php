<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application\SignIn;

use AlfonsoSG\Mvc\Security\Application\SignIn\SignInCommand;
use AlfonsoSG\Mvc\Security\Application\SignIn\SignInHandler;
use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
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
}
