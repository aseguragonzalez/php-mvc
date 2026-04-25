<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Commands\ConsoleOutput;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConsoleOutputTest extends TestCase
{
    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    protected function setUp(): void
    {
        $stdout = fopen('php://memory', 'r+');
        $stderr = fopen('php://memory', 'r+');
        \assert(\is_resource($stdout));
        \assert(\is_resource($stderr));
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    protected function tearDown(): void
    {
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
        if (is_resource($this->stderr)) {
            fclose($this->stderr);
        }
    }

    public function testInfoWritesToStdout(): void
    {
        $output = new ConsoleOutput($this->stdout, $this->stderr);

        $output->info('test message');

        $this->assertStringContainsString('test message', $this->readStream($this->stdout));
    }

    public function testSuccessWritesToStdout(): void
    {
        $output = new ConsoleOutput($this->stdout, $this->stderr);

        $output->success('done');

        $this->assertStringContainsString('done', $this->readStream($this->stdout));
    }

    public function testErrorWritesToStderr(): void
    {
        $output = new ConsoleOutput($this->stdout, $this->stderr);

        $output->error('something failed');

        $this->assertStringContainsString('something failed', $this->readStream($this->stderr));
    }

    public function testLineWritesToStdout(): void
    {
        $output = new ConsoleOutput($this->stdout, $this->stderr);

        $output->line('plain text');

        $this->assertSame("plain text\n", $this->readStream($this->stdout));
    }

    public function testEmptyLineWritesNewline(): void
    {
        $output = new ConsoleOutput($this->stdout, $this->stderr);

        $output->line();

        $this->assertSame("\n", $this->readStream($this->stdout));
    }

    /**
     * @param resource $stream
     */
    private function readStream(mixed $stream): string
    {
        rewind($stream);
        $content = stream_get_contents($stream);

        return false !== $content ? $content : '';
    }
}
