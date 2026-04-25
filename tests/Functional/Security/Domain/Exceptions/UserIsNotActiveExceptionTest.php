<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Domain\Exceptions;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\UserIsNotActiveException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserIsNotActiveExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithUsername(): void
    {
        $exception = new UserIsNotActiveException('inactive@example.com');

        $this->assertSame('User is not active: inactive@example.com.', $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenUsernameIsEmpty(): void
    {
        $exception = new UserIsNotActiveException();

        $this->assertSame('User is not active: .', $exception->getMessage());
    }
}
