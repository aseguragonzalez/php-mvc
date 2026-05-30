<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\BackgroundTasks;

use PhpMvc\BackgroundTasks\BackgroundTasksBootstrap;
use PhpMvc\MutableContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BackgroundTasksBootstrapTest extends TestCase
{
    public function testConfigureRegistersAllBindings(): void
    {
        $container = $this->createMock(MutableContainerInterface::class);
        $container->method('get')->willReturn(null);
        $container->expects($this->atLeastOnce())->method('set');

        BackgroundTasksBootstrap::configure($container);
    }
}
