<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security;

use PhpMvc\MutableContainerInterface;
use PhpMvc\Security\Dependencies;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DependenciesTest extends TestCase
{
    public function testConfigureRegistersAllBindings(): void
    {
        $container = $this->createMock(MutableContainerInterface::class);
        $container->method('get')->willReturn(null);
        $container->expects($this->atLeastOnce())->method('set');

        Dependencies::configure($container);
    }
}
