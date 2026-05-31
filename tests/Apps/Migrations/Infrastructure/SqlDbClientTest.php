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

    public function testBeginTransactionDelegatesToPdo(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('beginTransaction');

        $client = new SqlDbClient($pdo);
        $client->beginTransaction();
    }

    public function testCommitDelegatesToPdo(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('commit');

        $client = new SqlDbClient($pdo);
        $client->commit();
    }

    public function testRollBackDelegatesToPdo(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('rollBack');

        $client = new SqlDbClient($pdo);
        $client->rollBack();
    }

    public function testInTransactionReturnsBoolFromPdo(): void
    {
        $pdo = $this->createStub(\PDO::class);
        $pdo->method('inTransaction')->willReturn(true);

        $client = new SqlDbClient($pdo);

        $this->assertTrue($client->inTransaction());
    }

    public function testExecuteCallsExecForEachStatement(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->exactly(2))
            ->method('exec')
            ->willReturnCallback(function (string $sql): int {
                return 0;
            })
        ;

        $client = new SqlDbClient($pdo);
        $client->execute(['CREATE TABLE a (id INT)', 'CREATE TABLE b (id INT)']);
    }
}
