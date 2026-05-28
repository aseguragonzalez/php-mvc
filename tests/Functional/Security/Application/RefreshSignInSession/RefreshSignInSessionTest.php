<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\RefreshSignInSession;

use PhpMvc\Security\Application\RefreshSignInSession\RefreshSignInSessionCommand;
use PhpMvc\Security\Application\RefreshSignInSession\RefreshSignInSessionHandler;
use PhpMvc\Security\Challenge;
use PhpMvc\Security\ChallengesExpirationTime;
use PhpMvc\Security\Domain\Entities\SignInSession;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
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
