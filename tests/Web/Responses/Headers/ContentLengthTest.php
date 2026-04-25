<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Responses\Headers;

use AlfonsoSG\Mvc\Responses\Headers\ContentLength;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContentLengthTest extends TestCase
{
    public function testContentLengthHeaderIsCreatedSuccessfully(): void
    {
        $length = 123;
        $header = new ContentLength($length);

        $this->assertSame('Content-Length', $header->name);
        $this->assertSame('123', $header->value);
    }

    public function testContentLengthHeaderWithZeroLength(): void
    {
        $length = 0;
        $header = new ContentLength($length);

        $this->assertSame('Content-Length', $header->name);
        $this->assertSame('0', $header->value);
    }

    public function testContentLengthHeaderWithNegativeLengthThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ContentLength(-1);
    }

    public function testContentLengthHeaderToStringMethod(): void
    {
        $length = 456;
        $header = new ContentLength($length);

        $this->assertSame('Content-Length: 456', (string) $header);
    }
}
