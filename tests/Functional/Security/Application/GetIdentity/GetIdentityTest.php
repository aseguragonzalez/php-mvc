<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\GetIdentity;

use PhpMvc\Security\Application\GetIdentity\GetIdentityCommand;
use PhpMvc\Security\Application\GetIdentity\GetIdentityHandler;
use PhpMvc\Security\Challenge;
use PhpMvc\Security\Domain\Entities\SignInSession;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Exceptions\SessionExpiredException;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GetIdentityTest extends TestCase
{
    public function testExecuteReturnsAnonymousForNullToken(): void
    {
        $handler = new GetIdentityHandler($this->createStub(SignInSessionRepository::class));

        $identity = $handler->execute(new GetIdentityCommand(null));

        $this->assertFalse($identity->isAuthenticated());
        $this->assertEquals('anonymous', $identity->username());
    }

    public function testExecuteReturnsAnonymousForEmptyStringToken(): void
    {
        $handler = new GetIdentityHandler($this->createStub(SignInSessionRepository::class));

        $identity = $handler->execute(new GetIdentityCommand(''));

        $this->assertFalse($identity->isAuthenticated());
        $this->assertEquals('anonymous', $identity->username());
    }

    public function testExecuteReturnsAnonymousForWhitespaceOnlyToken(): void
    {
        $handler = new GetIdentityHandler($this->createStub(SignInSessionRepository::class));

        $identity = $handler->execute(new GetIdentityCommand('   '));

        $this->assertFalse($identity->isAuthenticated());
        $this->assertEquals('anonymous', $identity->username());
    }

    public function testExecuteThrowsSessionExpiredExceptionWhenNoSessionFound(): void
    {
        $repo = $this->createStub(SignInSessionRepository::class);
        $repo->method('getByToken')->willReturn(null);

        $handler = new GetIdentityHandler($repo);

        $this->expectException(SessionExpiredException::class);
        $handler->execute(new GetIdentityCommand('valid-token'));
    }

    public function testExecuteThrowsSessionExpiredExceptionAndDeletesWhenSessionExpired(): void
    {
        $challenge = $this->createStub(Challenge::class);
        $challenge->method('isExpired')->willReturn(true);

        $user = UserIdentity::new('user@example.com', ['admin'], 'pass')->activate();
        $session = SignInSession::build($challenge, $user);

        $repo = $this->createMock(SignInSessionRepository::class);
        $repo->method('getByToken')->willReturn($session);
        $repo->expects($this->once())->method('deleteByToken')->with('valid-token');

        $handler = new GetIdentityHandler($repo);

        $this->expectException(SessionExpiredException::class);
        $handler->execute(new GetIdentityCommand('valid-token'));
    }

    public function testExecuteReturnsIdentityForValidSession(): void
    {
        $challenge = $this->createStub(Challenge::class);
        $challenge->method('isExpired')->willReturn(false);

        $user = UserIdentity::new('user@example.com', ['admin'], 'pass')->activate();
        $session = SignInSession::build($challenge, $user);

        $repo = $this->createStub(SignInSessionRepository::class);
        $repo->method('getByToken')->willReturn($session);

        $handler = new GetIdentityHandler($repo);

        $identity = $handler->execute(new GetIdentityCommand('valid-token'));

        $this->assertSame('user@example.com', $identity->username());
    }
}
