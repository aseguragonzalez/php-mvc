<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Routes;

use PhpMvc\Routes\InvalidController;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InvalidControllerTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $exception = new InvalidController('NonExistentController');

        $this->assertSame(
            'Controller NonExistentController is not a valid controller',
            $exception->getMessage()
        );
    }
}
