<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Responses\Headers;

use PhpMvc\Responses\Headers\LastModified;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class LastModifiedTest extends TestCase
{
    public function testLastModifiedHeaderIsSetCorrectly(): void
    {
        $dateTime = new \DateTimeImmutable('2023-10-01 12:00:00');
        $lastModified = new LastModified($dateTime);

        $this->assertSame('Last-Modified', $lastModified->name);
        $this->assertSame('Sun, 01 Oct 2023 12:00:00 GMT', $lastModified->value);
    }

    public function testLastModifiedHeaderWithDifferentDate(): void
    {
        $dateTime = new \DateTimeImmutable('2022-05-15 08:30:00');
        $lastModified = new LastModified($dateTime);

        $this->assertSame('Last-Modified', $lastModified->name);
        $this->assertSame('Sun, 15 May 2022 08:30:00 GMT', $lastModified->value);
    }

    public function testLastModifiedHeaderWithTimezone(): void
    {
        $dateTime = new \DateTimeImmutable('2023-10-01 12:00:00', new \DateTimeZone('America/New_York'));
        $lastModified = new LastModified($dateTime);

        $this->assertSame('Last-Modified', $lastModified->name);
        $this->assertSame('Sun, 01 Oct 2023 16:00:00 GMT', $lastModified->value);
    }

    public function testLastModifiedHeaderToString(): void
    {
        $dateTime = new \DateTimeImmutable('2023-10-01 12:00:00');
        $lastModified = new LastModified($dateTime);

        $this->assertSame('Last-Modified: Sun, 01 Oct 2023 12:00:00 GMT', (string) $lastModified);
    }
}
