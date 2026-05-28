<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\ModifyUserIdentityPassword;

use PhpMvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordCommand;
use PhpMvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordHandler;
use PhpMvc\Security\Challenge;
use PhpMvc\Security\Domain\Entities\SignInSession;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;
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
