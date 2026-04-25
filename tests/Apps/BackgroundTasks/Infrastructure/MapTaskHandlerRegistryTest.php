<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\BackgroundTasks\Infrastructure;

use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandler;
use AlfonsoSG\Mvc\BackgroundTasks\Infrastructure\MapTaskHandlerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MapTaskHandlerRegistryTest extends TestCase
{
    private MapTaskHandlerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new MapTaskHandlerRegistry();
    }

    public function testGetHandlerReturnsNullWhenNotRegistered(): void
    {
        $this->assertNull($this->registry->getHandler('unknown_type'));
    }

    public function testGetHandlerReturnsRegisteredHandler(): void
    {
        $handler = $this->createStub(TaskHandler::class);
        $this->registry->register('send_email', $handler);

        $this->assertSame($handler, $this->registry->getHandler('send_email'));
    }

    public function testGetHandlerReturnsLastRegisteredHandlerForSameType(): void
    {
        $handler1 = $this->createStub(TaskHandler::class);
        $handler2 = $this->createStub(TaskHandler::class);
        $this->registry->register('send_email', $handler1);
        $this->registry->register('send_email', $handler2);

        $this->assertSame($handler2, $this->registry->getHandler('send_email'));
    }

    public function testDifferentTypesCanHaveDifferentHandlers(): void
    {
        $handlerA = $this->createStub(TaskHandler::class);
        $handlerB = $this->createStub(TaskHandler::class);
        $this->registry->register('type_a', $handlerA);
        $this->registry->register('type_b', $handlerB);

        $this->assertSame($handlerA, $this->registry->getHandler('type_a'));
        $this->assertSame($handlerB, $this->registry->getHandler('type_b'));
    }
}
