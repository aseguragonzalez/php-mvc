<?php

declare(strict_types=1);

namespace Tests\Support\Psr7;

use Psr\Http\Message\StreamInterface;

final class TestStream implements StreamInterface
{
    private string $content = '';
    private int $position = 0;

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void {}

    public function detach(): mixed
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => strlen($this->content) + $offset,
            default => throw new \RuntimeException('Invalid whence value'),
        };
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $this->content = substr($this->content, 0, $this->position).$string;
        $this->position = strlen($this->content);

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $data = substr($this->content, $this->position, $length);
        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        $data = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $data;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return null;
    }
}
