<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Entities;

use PhpMvc\Security\Domain\Entities\SignInChallenge;
use PhpMvc\Security\Domain\Entities\SignInSession;
use PhpMvc\Security\Identity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignInSessionTest extends TestCase
{
    private Identity $identity;

    protected function setUp(): void
    {
        $this->identity = $this->createStub(Identity::class);
    }

    public function testNewCreatesSessionWithSignInChallengeAndIdentity(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $session = SignInSession::new($expiresAt, $this->identity);

        $this->assertInstanceOf(SignInSession::class, $session);
        $this->assertInstanceOf(SignInChallenge::class, $session->challenge);
        $this->assertSame($this->identity, $session->identity);
    }

    public function testBuildCreatesSessionWithGivenChallengeAndIdentity(): void
    {
        $challenge = SignInChallenge::new(new \DateTimeImmutable('+1 hour'));

        $session = SignInSession::build($challenge, $this->identity);

        $this->assertInstanceOf(SignInSession::class, $session);
        $this->assertSame($challenge, $session->challenge);
        $this->assertSame($this->identity, $session->identity);
    }

    public function testIsExpiredDelegatesToChallenge(): void
    {
        $challenge = SignInChallenge::new(new \DateTimeImmutable('-1 hour'));

        $session = SignInSession::build($challenge, $this->identity);

        $this->assertTrue($session->isExpired());
    }

    public function testRefreshUntilReturnsNewSessionWithRefreshedChallenge(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $session = SignInSession::new($expiresAt, $this->identity);
        $newExpiresAt = new \DateTimeImmutable('+2 hours');

        $refreshed = $session->refreshUntil($newExpiresAt);

        $this->assertInstanceOf(SignInSession::class, $refreshed);
        $this->assertNotSame($session, $refreshed);
        $this->assertEquals($newExpiresAt, $refreshed->challenge->getExpiresAt());
        $this->assertSame($this->identity, $refreshed->identity);
    }
}
