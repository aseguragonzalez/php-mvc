<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Migrations\Domain;

use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\Migrations\Domain\Clients\DbClient;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Script;
use AlfonsoSG\Mvc\Migrations\Domain\Services\RollbackExecutorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RollbackExecutorHandlerTest extends TestCase
{
    private const DATABASE_NAME = 'main_db';

    private DbClient&MockObject $dbClient;
    private RollbackExecutorHandler $rollbackExecutor;

    protected function setUp(): void
    {
        $this->dbClient = $this->createMock(DbClient::class);
        $this->rollbackExecutor = new RollbackExecutorHandler($this->dbClient, self::DATABASE_NAME);
    }

    public function testRollbackExecutesRollbackStatementsInReverseOrder(): void
    {
        $script1 = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users;', 'DROP TABLE users;');
        $script2 = $this->createScriptFromFile(
            '002_add_column.sql',
            'ALTER TABLE users ADD COLUMN name VARCHAR(255);',
            'ALTER TABLE users DROP COLUMN name;',
        );
        $scripts = [$script1, $script2];

        $this->dbClient->expects($this->exactly(2))->method('useDatabase')->with(self::DATABASE_NAME);
        $callCount = 0;
        $this->dbClient->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function (array $statements) use (&$callCount): void {
                ++$callCount;
                if (1 === $callCount) {
                    $this->assertSame(['ALTER TABLE users DROP COLUMN name;'], $statements);
                } else {
                    $this->assertSame(['DROP TABLE users;'], $statements);
                }
            })
        ;

        $this->rollbackExecutor->rollback($scripts);
    }

    public function testRollbackCallsGetRollbackStatementsOnEachScript(): void
    {
        $script1 = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users;', 'DROP TABLE users;');
        $script2 = $this->createScriptFromFile(
            '002_add_column.sql',
            'ALTER TABLE users ADD COLUMN name VARCHAR(255);',
            'ALTER TABLE users DROP COLUMN name;',
        );
        $scripts = [$script1, $script2];

        $this->dbClient->expects($this->exactly(2))->method('useDatabase')->with(self::DATABASE_NAME);
        $this->dbClient->expects($this->exactly(2))
            ->method('execute')
        ;

        $this->rollbackExecutor->rollback($scripts);
    }

    public function testRollbackCallsDbClientExecuteForEachScript(): void
    {
        $script1 = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users;', 'DROP TABLE users;');
        $script2 = $this->createScriptFromFile(
            '002_add_column.sql',
            'ALTER TABLE users ADD COLUMN name VARCHAR(255);',
            'ALTER TABLE users DROP COLUMN name;',
        );
        $script3 = $this->createScriptFromFile(
            '003_add_index.sql',
            'CREATE INDEX idx_name ON users(name);',
            'DROP INDEX idx_name;',
        );
        $scripts = [$script1, $script2, $script3];

        $this->dbClient->expects($this->exactly(3))->method('useDatabase')->with(self::DATABASE_NAME);
        $callCount = 0;
        $this->dbClient->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function (array $statements) use (&$callCount): void {
                ++$callCount;
                if (1 === $callCount) {
                    $this->assertSame(['DROP INDEX idx_name;'], $statements);
                } elseif (2 === $callCount) {
                    $this->assertSame(['ALTER TABLE users DROP COLUMN name;'], $statements);
                } else {
                    $this->assertSame(['DROP TABLE users;'], $statements);
                }
            })
        ;

        $this->rollbackExecutor->rollback($scripts);
    }

    public function testMultipleScriptsAreRolledBackInCorrectOrder(): void
    {
        $script1 = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users;', 'DROP TABLE users;');
        $script2 = $this->createScriptFromFile(
            '002_add_column.sql',
            'ALTER TABLE users ADD COLUMN name VARCHAR(255);',
            'ALTER TABLE users DROP COLUMN name;',
        );
        $script3 = $this->createScriptFromFile(
            '003_add_index.sql',
            'CREATE INDEX idx_name ON users(name);',
            'DROP INDEX idx_name;',
        );
        $scripts = [$script1, $script2, $script3];

        $this->dbClient->expects($this->exactly(3))->method('useDatabase')->with(self::DATABASE_NAME);
        $callOrder = [];
        $this->dbClient->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function (array $statements) use (&$callOrder): void {
                $callOrder[] = $statements[0] ?? '';
            })
        ;

        $this->rollbackExecutor->rollback($scripts);

        $this->assertSame('DROP INDEX idx_name;', $callOrder[0]);
        $this->assertSame('ALTER TABLE users DROP COLUMN name;', $callOrder[1]);
        $this->assertSame('DROP TABLE users;', $callOrder[2]);
    }

    public function testRollbackWithSingleScript(): void
    {
        $script = $this->createScriptFromFile('001_create_table.sql', 'CREATE TABLE users;', 'DROP TABLE users;');
        $scripts = [$script];

        $this->dbClient->expects($this->once())->method('useDatabase')->with(self::DATABASE_NAME);
        $this->dbClient->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(['DROP TABLE users;']))
        ;

        $this->rollbackExecutor->rollback($scripts);
    }

    public function testRollbackWithEmptyScriptsArray(): void
    {
        $scripts = [];

        $this->dbClient->expects($this->never())->method('useDatabase');
        $this->dbClient->expects($this->never())
            ->method('execute')
        ;

        $this->rollbackExecutor->rollback($scripts);
    }

    public function testRollbackHandlesMultipleRollbackStatementsPerScript(): void
    {
        $rollbackContent = 'DROP TABLE users; DROP TABLE posts;';
        $script = $this->createScriptFromFile(
            '001_create_tables.sql',
            'CREATE TABLE users; CREATE TABLE posts;',
            $rollbackContent,
        );
        $scripts = [$script];

        $this->dbClient->expects($this->once())->method('useDatabase')->with(self::DATABASE_NAME);
        $this->dbClient->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(['DROP TABLE users;', 'DROP TABLE posts;']))
        ;

        $this->rollbackExecutor->rollback($scripts);
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
