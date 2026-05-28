<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc;

use PhpMvc\ResponseEmitter;
use PHPUnit\Framework\TestCase;
use Tests\Support\Psr7\TestPsr17Factory;
use Tests\Support\Psr7\TestStream;

/**
 * @internal
 *
 * @coversNothing
 */
final class ResponseEmitterTest extends TestCase
{
    public function testEmitOutputsResponseBody(): void
    {
        $factory = new TestPsr17Factory();
        $body = new TestStream();
        $body->write('Hello World');
        $response = $factory->createResponse(200)->withBody($body);

        ob_start();
        ResponseEmitter::emit($response);
        $output = ob_get_clean();

        $this->assertSame('Hello World', $output);
    }

    public function testEmitOutputsEmptyBodyForEmptyResponse(): void
    {
        $factory = new TestPsr17Factory();
        $response = $factory->createResponse(204);

        ob_start();
        ResponseEmitter::emit($response);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
