<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Routes;

use AlfonsoSG\Mvc\Routes\InvalidAction;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InvalidActionTest extends TestCase
{
    public function testExceptionHasExpectedMessage(): void
    {
        $exception = new InvalidAction('MyController', 'invalidAction');

        $this->assertSame(
            "Action 'invalidAction' is not a valid action for controller MyController",
            $exception->getMessage()
        );
    }
}
