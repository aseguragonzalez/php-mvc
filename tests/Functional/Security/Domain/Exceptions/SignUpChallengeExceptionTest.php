<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Exceptions;

use PhpMvc\Security\Domain\Exceptions\SignUpChallengeException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignUpChallengeExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithToken(): void
    {
        $exception = new SignUpChallengeException('xyz789');

        $this->assertSame('Invalid or expired sign-up token: xyz789', $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenTokenIsEmpty(): void
    {
        $exception = new SignUpChallengeException();

        $this->assertSame('Invalid or expired sign-up token: ', $exception->getMessage());
    }
}
