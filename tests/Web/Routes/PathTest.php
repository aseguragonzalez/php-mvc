<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Routes;

use PhpMvc\Routes\Path;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PathTest extends TestCase
{
    public function testCreate(): void
    {
        $path = Path::create('/example/path');
        $this->assertInstanceOf(Path::class, $path);
        $this->assertEquals('/example/path', $path->value());
    }

    public function testValue(): void
    {
        $path = Path::create('/example/path');
        $this->assertEquals('/example/path', $path->value());
    }

    public function testEquals(): void
    {
        $path1 = Path::create('/example/path');
        $path2 = Path::create('/example/path');
        $path3 = Path::create('/different/path');

        $this->assertTrue($path1->equals($path2));
        $this->assertFalse($path1->equals($path3));
    }

    public function testToString(): void
    {
        $path = Path::create('/example/path');
        $this->assertEquals('/example/path', (string) $path);
    }
}
