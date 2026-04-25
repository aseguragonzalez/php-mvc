<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Responses\Headers;

use AlfonsoSG\Mvc\Responses\Headers\Expires;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ExpiresTest extends TestCase
{
    public function testExpiresHeader(): void
    {
        $date = new \DateTimeImmutable('2023-10-01 12:00:00');
        $expires = new Expires($date);

        $this->assertSame('Expires', $expires->name);
        $this->assertSame('Sun, 01 Oct 2023 12:00:00 GMT', $expires->value);
    }

    public function testExpiresHeaderWithDifferentDate(): void
    {
        $date = new \DateTimeImmutable('2023-12-25 18:30:00');
        $expires = new Expires($date);

        $this->assertSame('Expires', $expires->name);
        $this->assertSame('Mon, 25 Dec 2023 18:30:00 GMT', $expires->value);
    }

    public function testExpiresHeaderToString(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 00:00:00');
        $expires = new Expires($date);

        $this->assertSame('Expires: Mon, 01 Jan 2024 00:00:00 GMT', (string) $expires);
    }
}
