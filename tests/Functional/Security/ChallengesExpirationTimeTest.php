<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security;

use PhpMvc\Security\ChallengesExpirationTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ChallengesExpirationTimeTest extends TestCase
{
    public function testItCanBeCreatedWithValidExpirationTimes(): void
    {
        $expiration = new ChallengesExpirationTime(
            signUp: 100,
            signIn: 200,
            signInWithRememberMe: 300,
            refresh: 400,
            resetPasswordChallenge: 500
        );
        $this->assertInstanceOf(ChallengesExpirationTime::class, $expiration);
        $this->assertSame(100, $expiration->signUp);
        $this->assertSame(200, $expiration->signIn);
        $this->assertSame(300, $expiration->signInWithRememberMe);
        $this->assertSame(400, $expiration->refresh);
        $this->assertSame(500, $expiration->resetPasswordChallenge);
    }

    public function testItThrowsExceptionForNonPositiveSignUp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ChallengesExpirationTime(signUp: 0);
    }

    public function testItThrowsExceptionForNonPositiveSignIn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ChallengesExpirationTime(signIn: 0);
    }

    public function testItThrowsExceptionForNonPositiveSignInWithRememberMe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ChallengesExpirationTime(signInWithRememberMe: 0);
    }

    public function testItThrowsExceptionForNonPositiveResetPasswordChallenge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ChallengesExpirationTime(resetPasswordChallenge: 0);
    }

    public function testDefaultValues(): void
    {
        $expiration = new ChallengesExpirationTime();
        $this->assertSame(60 * 24, $expiration->signUp);
        $this->assertSame(60, $expiration->signIn);
        $this->assertSame(60 * 24 * 30, $expiration->signInWithRememberMe);
        $this->assertSame(60, $expiration->refresh);
        $this->assertSame(60 * 24, $expiration->resetPasswordChallenge);
    }
}
