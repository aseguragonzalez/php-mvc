<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Infrastructure;

use PhpMvc\Migrations\Infrastructure\SqlDbClient;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlDbClientTest extends TestCase
{
    public function testUseDatabaseExecutesUseStatement(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with('USE `my_db`')
        ;

        $client = new SqlDbClient($pdo);
        $client->useDatabase('my_db');
    }

    public function testUseDatabaseEscapesBackticksInDatabaseName(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with('USE `my``db`')
        ;

        $client = new SqlDbClient($pdo);
        $client->useDatabase('my`db');
    }
}
