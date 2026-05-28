<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Exceptions;

use PhpMvc\Security\Domain\Exceptions\UsernameIsNotEmailException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UsernameIsNotEmailExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithUsername(): void
    {
        $exception = new UsernameIsNotEmailException('invalid');

        $this->assertSame('Username is not a valid email address: invalid.', $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenUsernameIsEmpty(): void
    {
        $exception = new UsernameIsNotEmailException();

        $this->assertSame('Username is not a valid email address: .', $exception->getMessage());
    }
}
