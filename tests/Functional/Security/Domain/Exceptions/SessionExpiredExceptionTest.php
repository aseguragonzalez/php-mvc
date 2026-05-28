<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Exceptions;

use PhpMvc\Security\Domain\Exceptions\SessionExpiredException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SessionExpiredExceptionTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $exception = new SessionExpiredException();

        $this->assertSame('Session has expired.', $exception->getMessage());
    }
}
