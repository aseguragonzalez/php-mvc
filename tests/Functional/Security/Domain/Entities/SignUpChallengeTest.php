<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Entities;

use PhpMvc\Security\Domain\Entities\SignUpChallenge;
use PhpMvc\Security\Domain\Entities\UserIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignUpChallengeTest extends TestCase
{
    private UserIdentity $userIdentity;

    public function setUp(): void
    {
        $this->userIdentity = UserIdentity::new('user@domain.com', ['role'], 'pass');
    }

    public function testNewGeneratesValidTokenAndExpiration(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $challenge = SignUpChallenge::new($expiresAt, $this->userIdentity);
        $this->assertInstanceOf(SignUpChallenge::class, $challenge);
        $this->assertNotEmpty($challenge->getToken());
        $this->assertEquals($expiresAt, $challenge->expiresAt);
        $this->assertFalse($challenge->isExpired());
    }

    public function testBuildCreatesChallengeWithGivenTokenAndExpiration(): void
    {
        $token = 'testtoken';
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $challenge = SignUpChallenge::build($token, $expiresAt, $this->userIdentity);
        $this->assertInstanceOf(SignUpChallenge::class, $challenge);
        $this->assertEquals($token, $challenge->getToken());
        $this->assertEquals($expiresAt, $challenge->expiresAt);
    }

    public function testIsExpiredReturnsTrueIfExpired(): void
    {
        $expiresAt = new \DateTimeImmutable('-1 hour');
        $challenge = SignUpChallenge::new($expiresAt, $this->userIdentity);
        $this->assertTrue($challenge->isExpired());
    }

    public function testRefreshUntilReturnsNewChallengeWithUpdatedExpiration(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $challenge = SignUpChallenge::new($expiresAt, $this->userIdentity);
        $newExpiresAt = new \DateTimeImmutable('+2 hours');
        $refreshed = $challenge->refreshUntil($newExpiresAt);
        $this->assertInstanceOf(SignUpChallenge::class, $refreshed);
        $this->assertEquals($challenge->getToken(), $refreshed->getToken());
        $this->assertEquals($newExpiresAt, $refreshed->expiresAt);
    }
}
