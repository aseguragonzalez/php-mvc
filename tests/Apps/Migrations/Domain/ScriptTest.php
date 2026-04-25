<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Migrations\Domain;

use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Script;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ScriptTest extends TestCase
{
    private FileManager&Stub $fileManager;

    protected function setUp(): void
    {
        $this->fileManager = $this->createStub(FileManager::class);
    }

    public function testFromFileReadsContentAndRollbackContentUsingFileManager(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT);';
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $this->assertSame($fileName, $script->fileName);
        $this->assertSame($content, $script->content);
        $this->assertSame($rollbackContent, $script->rollbackContent);
    }

    public function testBuildCreatesScriptWithoutContent(): void
    {
        $fileName = '001_create_table.sql';

        $script = Script::build($fileName);

        $this->assertSame($fileName, $script->fileName);
        $this->assertNull($script->content);
        $this->assertNull($script->rollbackContent);
    }

    public function testGetStatementsParsesSqlStatementsCorrectly(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT); CREATE TABLE posts (id INT);';
        $rollbackContent = 'DROP TABLE users;';
        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $statements = $script->getStatements();

        $this->assertCount(2, $statements);
        $this->assertSame('CREATE TABLE users (id INT);', $statements[0]);
        $this->assertSame('CREATE TABLE posts (id INT);', $statements[1]);
    }

    public function testGetStatementsFiltersEmptyStatementsAndComments(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content
            = "-- This is a comment\nCREATE TABLE users (id INT);\n-- Another comment\nCREATE TABLE posts (id INT);";
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $statements = $script->getStatements();

        $this->assertCount(2, $statements);
        $this->assertSame('CREATE TABLE users (id INT);', $statements[0]);
        $this->assertSame('CREATE TABLE posts (id INT);', $statements[1]);
        $this->assertNotContains('-- This is a comment', $statements);
        $this->assertNotContains('', $statements);
    }

    public function testGetStatementsThrowsWhenContentNotLoaded(): void
    {
        $script = Script::build('001_create_table.sql');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Script is not loaded');

        $script->getStatements();
    }

    public function testGetRollbackStatementsParsesRollbackStatementsCorrectly(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT);';
        $rollbackContent = 'DROP TABLE users; DROP TABLE posts;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);
        $rollbackStatements = $script->getRollbackStatements();

        $this->assertCount(2, $rollbackStatements);
        $this->assertSame('DROP TABLE users;', $rollbackStatements[0]);
        $this->assertSame('DROP TABLE posts;', $rollbackStatements[1]);
    }

    public function testGetRollbackStatementsThrowsWhenRollbackContentNotLoaded(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT);';
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $rollbackStatements = $script->getRollbackStatements();
        $this->assertCount(1, $rollbackStatements);

        $scriptWithoutRollback = Script::build('002_add_column.sql');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Script is not loaded');

        $scriptWithoutRollback->getRollbackStatements();
    }

    public function testMultipleStatementsSeparatedBySemicolons(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT); INSERT INTO users VALUES (1); UPDATE users SET id = 2;';
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $statements = $script->getStatements();

        $this->assertCount(3, $statements);
        $this->assertSame('CREATE TABLE users (id INT);', $statements[0]);
        $this->assertSame('INSERT INTO users VALUES (1);', $statements[1]);
        $this->assertSame('UPDATE users SET id = 2;', $statements[2]);
    }

    public function testGetRollbackStatementsFiltersCommentsAndEmptyStatements(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT);';
        $rollbackContent = "-- Comment\nDROP TABLE users;\n-- Another comment\nDROP TABLE posts;";

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $rollbackStatements = $script->getRollbackStatements();

        $this->assertCount(2, $rollbackStatements);
        $this->assertSame('DROP TABLE users;', $rollbackStatements[0]);
        $this->assertSame('DROP TABLE posts;', $rollbackStatements[1]);
        $this->assertNotContains('-- Comment', $rollbackStatements);
    }

    public function testGetStatementsFiltersOutUseStatement(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = "USE reservations;\nCREATE TABLE users (id INT);";
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $statements = $script->getStatements();

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE users (id INT);', $statements[0]);
        $this->assertNotContains('USE reservations;', $statements);
    }

    public function testGetStatementsFiltersOutUseStatementWithBackticks(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = "USE `reservations`;\nCREATE TABLE users (id INT);";
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $statements = $script->getStatements();

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE users (id INT);', $statements[0]);
    }

    public function testGetRollbackStatementsFiltersOutUseStatement(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'CREATE TABLE users (id INT);';
        $rollbackContent = "USE reservations;\nDROP TABLE users;";

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $rollbackStatements = $script->getRollbackStatements();

        $this->assertCount(1, $rollbackStatements);
        $this->assertSame('DROP TABLE users;', $rollbackStatements[0]);
        $this->assertNotContains('USE reservations;', $rollbackStatements);
    }

    public function testGetStatementsReturnsEmptyArrayWhenOnlyUseStatements(): void
    {
        $basePath = '/migrations/20240115';
        $fileName = '001_create_table.sql';
        $content = 'USE reservations;';
        $rollbackContent = 'DROP TABLE users;';

        $this->fileManager->method('readTextPlain')->willReturn($content, $rollbackContent);

        $script = Script::fromFile(basePath: $basePath, fileName: $fileName, fileManager: $this->fileManager);

        $statements = $script->getStatements();

        $this->assertCount(0, $statements);
    }
}
