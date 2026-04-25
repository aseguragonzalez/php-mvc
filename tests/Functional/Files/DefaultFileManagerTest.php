<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Files;

use AlfonsoSG\Mvc\Files\DefaultFileManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class DefaultFileManagerTest extends TestCase
{
    private DefaultFileManager $manager;
    private ?string $tmpFile = null;

    protected function setUp(): void
    {
        $this->manager = new DefaultFileManager();
        $tmp = tempnam(sys_get_temp_dir(), 'filemgr_');
        if (false === $tmp) {
            $this->fail('Failed to create temporary file');
        }
        $this->tmpFile = $tmp;
    }

    protected function tearDown(): void
    {
        if ($this->tmpFile && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        $this->tmpFile = null;
    }

    public function testReadTextPlainReturnsFileContents(): void
    {
        /** @var string $file */
        $file = $this->tmpFile;
        file_put_contents($file, 'hello world');
        $result = $this->manager->readTextPlain($file);
        $this->assertSame('hello world', $result);
    }

    public function testReadTextPlainThrowsIfFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/File not found/');
        $this->manager->readTextPlain('/nonexistent/file.txt');
    }

    public function testReadTextPlainThrowsIfReadFails(): void
    {
        /** @var string $file */
        $file = $this->tmpFile;
        if ($file && file_exists($file)) {
            unlink($file);
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/File not found/');
        $this->manager->readTextPlain($file);
    }

    public function testReadKeyValueJsonReturnsArray(): void
    {
        /** @var string $file */
        $file = $this->tmpFile;
        $data = ['foo' => 'bar', 'baz' => 'qux'];
        file_put_contents($file, json_encode($data));
        $result = $this->manager->readKeyValueJson($file);
        $this->assertSame($data, $result);
    }

    public function testReadKeyValueJsonThrowsIfFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/File not found/');
        $this->manager->readKeyValueJson('/nonexistent/file.json');
    }

    public function testReadKeyValueJsonThrowsIfReadFails(): void
    {
        /** @var string $file */
        $file = $this->tmpFile;
        if ($file && file_exists($file)) {
            unlink($file);
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/File not found/');
        $this->manager->readKeyValueJson($file);
    }

    public function testReadKeyValueJsonThrowsIfJsonInvalid(): void
    {
        /** @var string $file */
        $file = $this->tmpFile;
        file_put_contents($file, '{invalid json');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to decode JSON/');
        $this->manager->readKeyValueJson($file);
    }

    public function testReadTextPlainThrowsIfFileUnreadable(): void
    {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('vfsStream is required for this test');
        }
        $root = vfsStream::setup('root');
        $file = vfsStream::newFile('unreadable.txt', 0o000)->at($root);
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            return true;
        });

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/Failed to read file/');
            $this->manager->readTextPlain($file->url());
        } finally {
            restore_error_handler();
        }
    }

    public function testReadKeyValueJsonThrowsIfFileUnreadable(): void
    {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('vfsStream is required for this test');
        }
        $root = vfsStream::setup('root');
        $file = vfsStream::newFile('unreadable.json', 0o000)->at($root);
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            return true;
        });

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/Failed to read file/');
            $this->manager->readKeyValueJson($file->url());
        } finally {
            restore_error_handler();
        }
    }
}
