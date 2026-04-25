<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Domain\Exceptions;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\UserBlockedException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserBlockedExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithUsername(): void
    {
        $exception = new UserBlockedException('blocked@example.com');

        $this->assertSame('User is blocked: blocked@example.com.', $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenUsernameIsEmpty(): void
    {
        $exception = new UserBlockedException();

        $this->assertSame('User is blocked: .', $exception->getMessage());
    }
}
