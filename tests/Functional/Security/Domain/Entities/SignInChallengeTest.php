<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Domain\Entities;

use AlfonsoSG\Mvc\Security\Domain\Entities\SignInChallenge;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignInChallengeTest extends TestCase
{
    public function testNewGeneratesValidTokenAndExpiration(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $challenge = SignInChallenge::new($expiresAt);
        $this->assertInstanceOf(SignInChallenge::class, $challenge);
        $this->assertNotEmpty($challenge->getToken());
        $this->assertEquals($expiresAt, $challenge->expiresAt);
        $this->assertFalse($challenge->isExpired());
    }

    public function testBuildCreatesChallengeWithGivenTokenAndExpiration(): void
    {
        $token = 'testtoken';
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $challenge = SignInChallenge::build($token, $expiresAt);
        $this->assertInstanceOf(SignInChallenge::class, $challenge);
        $this->assertEquals($token, $challenge->getToken());
        $this->assertEquals($expiresAt, $challenge->expiresAt);
    }

    public function testIsExpiredReturnsTrueIfExpired(): void
    {
        $expiresAt = new \DateTimeImmutable('-1 hour');
        $challenge = SignInChallenge::new($expiresAt);
        $this->assertTrue($challenge->isExpired());
    }

    public function testRefreshUntilReturnsNewChallengeWithUpdatedExpiration(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $challenge = SignInChallenge::new($expiresAt);
        $newExpiresAt = new \DateTimeImmutable('+2 hours');
        $refreshed = $challenge->refreshUntil($newExpiresAt);
        $this->assertInstanceOf(SignInChallenge::class, $refreshed);
        $this->assertEquals($challenge->getToken(), $refreshed->getToken());
        $this->assertEquals($newExpiresAt, $refreshed->expiresAt);
    }
}
