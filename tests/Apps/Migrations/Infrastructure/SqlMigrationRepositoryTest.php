<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Infrastructure;

use PhpMvc\Migrations\Domain\Entities\Migration;
use PhpMvc\Migrations\Domain\Entities\Script;
use PhpMvc\Migrations\Infrastructure\SqlMigrationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlMigrationRepositoryTest extends TestCase
{
    private MockObject&\PDO $pdo;
    private MockObject&\PDOStatement $statement;
    private SqlMigrationRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->statement = $this->createMock(\PDOStatement::class);
        $this->repository = new SqlMigrationRepository($this->pdo);
    }

    public function testSaveInsertsEachScript(): void
    {
        $migration = Migration::new(
            name: '20240115',
            scripts: [Script::build('001_create.sql'), Script::build('002_alter.sql')],
        );

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO migrations_history'))
            ->willReturn($this->statement)
        ;
        $this->statement->expects($this->exactly(2))
            ->method('execute')
            ->with($this->callback(function (array $params): bool {
                return isset($params['migration'], $params['filename'], $params['created_at'])
                    && '20240115' === $params['migration'];
            }))
        ;

        $this->repository->save($migration);
    }

    public function testSaveWithNoScriptsDoesNothing(): void
    {
        $migration = Migration::new(name: '20240115', scripts: []);

        $this->pdo->expects($this->never())->method('prepare');

        $this->repository->save($migration);
    }

    public function testGetMigrationsReturnsEmptyWhenNoRows(): void
    {
        $this->pdo->expects($this->once())->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->once())->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([])
        ;

        $migrations = $this->repository->getMigrations();

        $this->assertSame([], $migrations);
    }

    public function testGetMigrationsGroupsRowsByMigrationName(): void
    {
        $createdAt = '2024-01-15 10:00:00';
        $rows = [
            ['migration' => '20240115', 'filename' => '001_create.sql', 'created_at' => $createdAt],
            ['migration' => '20240115', 'filename' => '002_alter.sql', 'created_at' => $createdAt],
            ['migration' => '20240116', 'filename' => '001_add_index.sql', 'created_at' => '2024-01-16 10:00:00'],
        ];

        $this->pdo->expects($this->once())->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->once())->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($rows)
        ;

        $migrations = $this->repository->getMigrations();

        $this->assertCount(2, $migrations);
        $this->assertSame('20240115', $migrations[0]->name);
        $this->assertCount(2, $migrations[0]->scripts);
        $this->assertSame('20240116', $migrations[1]->name);
        $this->assertCount(1, $migrations[1]->scripts);
    }
}
