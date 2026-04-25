<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Domain\Exceptions;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\ResetPasswordChallengeException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ResetPasswordChallengeExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithToken(): void
    {
        $exception = new ResetPasswordChallengeException('abc123');

        $this->assertSame('Invalid or expired reset password token: abc123', $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenTokenIsEmpty(): void
    {
        $exception = new ResetPasswordChallengeException();

        $this->assertSame('Invalid or expired reset password token: ', $exception->getMessage());
    }
}
