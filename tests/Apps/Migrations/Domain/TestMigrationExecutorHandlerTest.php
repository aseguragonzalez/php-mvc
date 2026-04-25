<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Migrations\Domain;

use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\Migrations\Domain\Clients\DbClient;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Script;
use AlfonsoSG\Mvc\Migrations\Domain\Services\TestMigrationExecutorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TestMigrationExecutorHandlerTest extends TestCase
{
    private const DATABASE_NAME = 'main_db';
    private DbClient&MockObject $dbClient;
    private TestMigrationExecutorHandler $executor;

    protected function setUp(): void
    {
        $this->dbClient = $this->createMock(DbClient::class);
        $this->executor = new TestMigrationExecutorHandler($this->dbClient, self::DATABASE_NAME);
    }

    public function testExecuteRunsAllScriptsInMigration(): void
    {
        $script1 = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users (id INT);');
        $script2 = $this->createScriptFromFile(
            '002_add_column.sql',
            'ALTER TABLE users ADD COLUMN name VARCHAR(255);',
        );
        $scripts = [$script1, $script2];
        $migration = Migration::new(name: 'test_migration', scripts: $scripts);

        $this->dbClient->expects($this->exactly(2))->method('useDatabase')->with(self::DATABASE_NAME);
        $this->dbClient->expects($this->exactly(2))->method('execute');

        $this->executor->execute($migration);
    }

    public function testExecuteDoesNotSaveToRepository(): void
    {
        $script = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users (id INT);');
        $migration = Migration::new(name: 'test_migration', scripts: [$script]);

        $this->dbClient->expects($this->once())->method('useDatabase')->with(self::DATABASE_NAME);
        $this->dbClient->expects($this->once())->method('execute');
        $this->dbClient->expects($this->never())->method('beginTransaction');
        $this->dbClient->expects($this->never())->method('commit');

        $this->executor->execute($migration);
    }

    private function createScriptFromFile(string $fileName, string $content, ?string $rollbackContent = null): Script
    {
        $fileManager = $this->createStub(FileManager::class);
        $basePath = '/test/migrations';

        if (null !== $rollbackContent) {
            $fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);
        } else {
            $fileManager->method('readTextPlain')->willReturn($content);
        }

        return Script::fromFile($basePath, $fileName, $fileManager);
    }
}
