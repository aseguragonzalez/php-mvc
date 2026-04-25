<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application\RefreshSignInSession;

use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSessionCommand;
use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSessionHandler;
use AlfonsoSG\Mvc\Security\Challenge;
use AlfonsoSG\Mvc\Security\ChallengesExpirationTime;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RefreshSignInSessionTest extends TestCase
{
    public function testExecuteRefreshesSessionAndReturnsChallenge(): void
    {
        $user = UserIdentity::new('user@example.com', ['admin'], 'pass');
        $challenge = $this->createStub(Challenge::class);
        $challenge->method('isExpired')->willReturn(false);
        $challenge->method('refreshUntil')->willReturn($challenge);
        $session = SignInSession::build($challenge, $user);

        $signInSessionRepository = $this->createMock(SignInSessionRepository::class);
        $signInSessionRepository->method('getByToken')->willReturn($session);
        $signInSessionRepository->expects($this->once())->method('save');

        $handler = new RefreshSignInSessionHandler(
            $signInSessionRepository,
            new ChallengesExpirationTime(10, 5, 20, 15, 30)
        );

        $result = $handler->execute(new RefreshSignInSessionCommand('token'));

        $this->assertInstanceOf(Challenge::class, $result);
    }
}
