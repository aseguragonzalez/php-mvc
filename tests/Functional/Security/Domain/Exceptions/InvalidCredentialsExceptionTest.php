<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Domain\Exceptions;

use AlfonsoSG\Mvc\Security\Domain\Exceptions\InvalidCredentialsException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InvalidCredentialsExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithUsername(): void
    {
        $exception = new InvalidCredentialsException('user@example.com');

        $expected = 'Invalid credentials for user: user@example.com or user does not exist.';
        $this->assertSame($expected, $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenUsernameIsEmpty(): void
    {
        $exception = new InvalidCredentialsException();

        $this->assertSame('Invalid credentials for user:  or user does not exist.', $exception->getMessage());
    }
}
