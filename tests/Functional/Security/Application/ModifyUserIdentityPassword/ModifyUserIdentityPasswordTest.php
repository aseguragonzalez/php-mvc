<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword;

use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordCommand;
use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordHandler;
use AlfonsoSG\Mvc\Security\Challenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ModifyUserIdentityPasswordTest extends TestCase
{
    public function testExecuteUpdatesPasswordWhenCurrentPasswordValid(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'old')->activate();
        $challenge = $this->createStub(Challenge::class);
        $challenge->method('isExpired')->willReturn(false);
        $session = SignInSession::build($challenge, $user);

        $signInSessionRepository = $this->createStub(SignInSessionRepository::class);
        $signInSessionRepository->method('getByToken')->willReturn($session);

        $userIdentityRepository = $this->createMock(UserIdentityRepository::class);
        $userIdentityRepository->method('getByUsername')->willReturn($user);
        $userIdentityRepository->expects($this->once())->method('save');

        $handler = new ModifyUserIdentityPasswordHandler($signInSessionRepository, $userIdentityRepository);

        $handler->execute(new ModifyUserIdentityPasswordCommand('token', 'old', 'new'));
    }
}
