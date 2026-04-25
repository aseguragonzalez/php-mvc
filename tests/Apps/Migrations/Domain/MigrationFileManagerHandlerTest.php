<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Migrations\Domain;

use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Script;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationFileManagerHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationFileManagerHandlerTest extends TestCase
{
    private FileManager&MockObject $fileManager;
    private MigrationFileManagerHandler $migrationFileManager;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->migrationFileManager = new MigrationFileManagerHandler($this->fileManager);
    }

    public function testGetMigrationsReturnsArrayOfMigrationObjects(): void
    {
        $basePath = '/migrations';
        $folders = ['20240115', '20240116'];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $this->setupFileManagerForFolders($basePath, [
            '20240115' => ['001_create_table.sql'],
            '20240116' => ['001_add_column.sql'],
        ]);

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(2, $migrations);
        $this->assertInstanceOf(Migration::class, $migrations[0]);
        $this->assertInstanceOf(Migration::class, $migrations[1]);
    }

    public function testGetMigrationsReadsFromFolderStructureCorrectly(): void
    {
        $basePath = '/migrations';
        $folders = ['20240115'];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $this->setupFileManagerForFolders($basePath, [
            '20240115' => ['001_create_table.sql', '002_add_column.sql'],
        ]);

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(1, $migrations);
        $this->assertSame('20240115', $migrations[0]->name);
        $this->assertCount(2, $migrations[0]->scripts);
    }

    public function testGetMigrationsFiltersFilesEndingWithRollback(): void
    {
        $basePath = '/migrations';
        $folders = ['20240115'];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $this->fileManager->expects($this->once())
            ->method('getFileNamesFromPath')
            ->with(
                "{$basePath}/20240115",
                ['sql'],
                ['rollback']
            )
            ->willReturn(['001_create_table.sql', '002_add_column.sql'])
        ;

        $this->setupScriptFileReads($basePath, '20240115', [
            '001_create_table.sql',
            '002_add_column.sql',
        ]);

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(1, $migrations);
        $migration = $migrations[0];
        $this->assertCount(2, $migration->scripts);
        $this->assertSame('001_create_table.sql', $migration->scripts[0]->fileName);
        $this->assertSame('002_add_column.sql', $migration->scripts[1]->fileName);
    }

    public function testGetMigrationsOnlyIncludesSqlFiles(): void
    {
        $basePath = '/migrations';
        $folders = ['20240115'];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $this->fileManager->expects($this->once())
            ->method('getFileNamesFromPath')
            ->with(
                "{$basePath}/20240115",
                ['sql'],
                ['rollback']
            )
            ->willReturn(['001_create_table.sql'])
        ;

        $this->setupScriptFileReads($basePath, '20240115', ['001_create_table.sql']);

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(1, $migrations);
        $migration = $migrations[0];
        $this->assertCount(1, $migration->scripts);
        $this->assertStringEndsWith('.sql', $migration->scripts[0]->fileName);
    }

    public function testGetMigrationsCreatesScriptObjectsViaScriptFromFile(): void
    {
        $basePath = '/migrations';
        $folders = ['20240115'];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $this->fileManager->expects($this->once())
            ->method('getFileNamesFromPath')
            ->with(
                "{$basePath}/20240115",
                ['sql'],
                ['rollback']
            )
            ->willReturn(['001_create_table.sql'])
        ;

        $filePath = "{$basePath}/20240115/001_create_table.sql";
        $rollbackFilePath = "{$basePath}/20240115/001_create_table.rollback.sql";
        $content = 'CREATE TABLE users (id INT);';
        $rollbackContent = 'DROP TABLE users;';

        $callCount = 0;
        $this->fileManager->expects($this->exactly(2))
            ->method('readTextPlain')
            ->willReturnCallback(
                function (string $path) use (
                    $filePath,
                    $rollbackFilePath,
                    $content,
                    $rollbackContent,
                    &$callCount
                ): string {
                    ++$callCount;
                    if (1 === $callCount) {
                        $this->assertSame($filePath, $path);

                        return $content;
                    }
                    $this->assertSame($rollbackFilePath, $path);

                    return $rollbackContent;
                }
            )
        ;

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(1, $migrations);
        $migration = $migrations[0];
        $this->assertCount(1, $migration->scripts);
        $this->assertInstanceOf(Script::class, $migration->scripts[0]);
        $this->assertSame('001_create_table.sql', $migration->scripts[0]->fileName);
        $this->assertSame($content, $migration->scripts[0]->content);
        $this->assertSame($rollbackContent, $migration->scripts[0]->rollbackContent);
    }

    public function testEmptyFolderReturnsEmptyArray(): void
    {
        $basePath = '/migrations';
        $folders = [];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(0, $migrations);
    }

    public function testGetMigrationsHandlesMultipleFolders(): void
    {
        $basePath = '/migrations';
        $folders = ['20240115', '20240116', '20240117'];

        $this->fileManager->expects($this->once())
            ->method('getFoldersFromPath')
            ->with($basePath)
            ->willReturn($folders)
        ;

        $this->setupFileManagerForFolders($basePath, [
            '20240115' => ['001_create_table.sql'],
            '20240116' => ['001_add_column.sql'],
            '20240117' => ['001_add_index.sql'],
        ]);

        $migrations = $this->migrationFileManager->getMigrations($basePath);

        $this->assertCount(3, $migrations);
        $this->assertSame('20240115', $migrations[0]->name);
        $this->assertSame('20240116', $migrations[1]->name);
        $this->assertSame('20240117', $migrations[2]->name);
    }

    /**
     * @param array<int, list<string>> $folderFiles
     */
    private function setupFileManagerForFolders(string $basePath, array $folderFiles): void
    {
        $this->fileManager->expects($this->exactly(count($folderFiles)))
            ->method('getFileNamesFromPath')
            ->willReturnCallback(
                function (
                    string $path,
                    array $extensions,
                    array $notEndsWith
                ) use (
                    $basePath,
                    $folderFiles
                ): array {
                    foreach ($folderFiles as $folder => $files) {
                        if (
                            $path === "{$basePath}/{$folder}"
                            && $extensions === ['sql']
                            && $notEndsWith === ['rollback']
                        ) {
                            return $files;
                        }
                    }

                    return [];
                }
            )
        ;

        foreach ($folderFiles as $folder => $files) {
            $this->setupScriptFileReads($basePath, "{$folder}", $files);
        }
    }

    /**
     * @param array<string> $files
     */
    private function setupScriptFileReads(string $basePath, string $folder, array $files): void
    {
        foreach ($files as $file) {
            $filePath = "{$basePath}/{$folder}/{$file}";
            $rollbackFilePath = str_replace('.sql', '.rollback.sql', $filePath);
            $content = 'CREATE TABLE test;';
            $rollbackContent = 'DROP TABLE test;';

            $this->fileManager
                ->method('readTextPlain')
                ->willReturnCallback(function ($path) use ($filePath, $rollbackFilePath, $content, $rollbackContent) {
                    if ($path === $filePath) {
                        return $content;
                    }
                    if ($path === $rollbackFilePath) {
                        return $rollbackContent;
                    }

                    return '';
                })
            ;
        }
    }
}
