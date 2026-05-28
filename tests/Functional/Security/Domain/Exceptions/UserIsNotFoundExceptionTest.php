<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Exceptions;

use PhpMvc\Security\Domain\Exceptions\UserIsNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserIsNotFoundExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessageWithUsername(): void
    {
        $exception = new UserIsNotFoundException('missing@example.com');

        $this->assertSame('User is not found: missing@example.com.', $exception->getMessage());
    }

    public function testExceptionHasExpectedMessageWhenUsernameIsEmpty(): void
    {
        $exception = new UserIsNotFoundException();

        $this->assertSame('User is not found: .', $exception->getMessage());
    }
}
