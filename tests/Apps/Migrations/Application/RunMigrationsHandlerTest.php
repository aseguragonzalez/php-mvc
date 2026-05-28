<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Application;

use PhpMvc\Migrations\Application\RunMigrationsHandler;
use PhpMvc\Migrations\Domain\Entities\Migration;
use PhpMvc\Migrations\Domain\Entities\Script;
use PhpMvc\Migrations\Domain\Exceptions\MigrationException;
use PhpMvc\Migrations\Domain\Repositories\MigrationRepository;
use PhpMvc\Migrations\Domain\Services\MigrationExecutor;
use PhpMvc\Migrations\Domain\Services\MigrationFileManager;
use PhpMvc\Migrations\Domain\Services\RollbackExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RunMigrationsHandlerTest extends TestCase
{
    private MigrationExecutor&Stub $migrationExecutor;
    private MigrationFileManager&MockObject $migrationFileManager;
    private MigrationRepository&MockObject $migrationRepository;
    private RollbackExecutor&Stub $rollbackExecutor;
    private RunMigrationsHandler $service;

    protected function setUp(): void
    {
        $this->migrationExecutor = $this->createStub(MigrationExecutor::class);
        $this->migrationFileManager = $this->createMock(MigrationFileManager::class);
        $this->migrationRepository = $this->createMock(MigrationRepository::class);
        $this->rollbackExecutor = $this->createStub(RollbackExecutor::class);
        $this->service = new RunMigrationsHandler(
            $this->migrationExecutor,
            $this->migrationFileManager,
            $this->migrationRepository,
            $this->rollbackExecutor
        );
    }

    public function testExecuteGetsAllMigrationsFromFileManager(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
            Migration::new(name: '20240116', scripts: []),
        ];
        $this->migrationFileManager
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->migrationExecutor->method('execute');

        $this->service->execute($basePath);
    }

    public function testExecuteGetsExecutedMigrationsFromRepository(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
        ];
        $executedMigrations = [
            Migration::new(name: '20240114', scripts: []),
        ];
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->with($basePath)
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn($executedMigrations)
        ;
        $this->migrationExecutor->method('execute');

        $this->service->execute($basePath);
    }

    public function testExecuteFiltersOutAlreadyExecutedMigrations(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
            Migration::new(name: '20240116', scripts: []),
            Migration::new(name: '20240117', scripts: []),
        ];
        $executedMigrations = [
            Migration::new(name: '20240115', scripts: []),
            Migration::new(name: '20240116', scripts: []),
        ];
        $this->migrationFileManager
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn($executedMigrations)
        ;
        $this->migrationExecutor
            ->method('execute')
            ->willReturnCallback(function (Migration $migration) {
                return '20240117' === $migration->name;
            })
        ;

        $this->service->execute($basePath);
    }

    public function testExecuteReturnsEarlyWhenNoPendingMigrations(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
        ];
        $executedMigrations = [
            Migration::new(name: '20240115', scripts: []),
        ];
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->with($basePath)
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn($executedMigrations)
        ;

        $this->service->execute($basePath);
    }

    public function testExecuteLogsGettingPendingMigrationsAtStart(): void
    {
        $basePath = '/migrations';
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;

        $this->service->execute($basePath);
    }

    public function testExecuteLogsNoPendingMigrationsFoundWhenEmpty(): void
    {
        $basePath = '/migrations';
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;

        $this->service->execute($basePath);
    }

    public function testExecuteLogsRunningMigrationsForAllDatabases(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
        ];
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->migrationExecutor->method('execute');

        $this->service->execute($basePath);
    }

    public function testExecuteExecutesAllPendingMigrations(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
            Migration::new(name: '20240116', scripts: []),
            Migration::new(name: '20240117', scripts: []),
        ];
        $executedMigrations = [];
        $this->migrationExecutor->method('execute')
            ->willReturnCallback(function (Migration $migration) use (&$executedMigrations): void {
                $executedMigrations[] = $migration->name;
            })
        ;
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->service->execute($basePath);

        $this->assertSame(['20240115', '20240116', '20240117'], $executedMigrations);
    }

    public function testExecuteLogsAllMigrationsCompletedSuccessfullyOnSuccess(): void
    {
        $basePath = '/migrations';
        $allMigrations = [
            Migration::new(name: '20240115', scripts: []),
        ];
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;

        $this->migrationExecutor->method('execute');

        $this->service->execute($basePath);
    }

    public function testExecuteCatchesMigrationExceptionAndTriggersRollback(): void
    {
        $basePath = '/migrations';
        $script1 = Script::build('001_create_table.sql');
        $script2 = Script::build('002_add_column.sql');
        $scripts = [$script1, $script2];
        $migration = Migration::new(name: '20240115', scripts: $scripts);
        $allMigrations = [$migration];
        $migrationException = new MigrationException(
            scripts: $scripts,
            message: 'Migration failed'
        );
        $this->migrationFileManager
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->migrationExecutor
            ->method('execute')
            ->willThrowException($migrationException)
        ;
        $this->rollbackExecutor->method('rollback');

        $this->service->execute($basePath);
    }

    public function testExecuteLogsRollbackForEachScriptOnError(): void
    {
        $basePath = '/migrations';
        $script1 = Script::build('001_create_table.sql');
        $script2 = Script::build('002_add_column.sql');
        $scripts = [$script1, $script2];
        $migration = Migration::new(name: '20240115', scripts: $scripts);
        $allMigrations = [$migration];
        $migrationException = new MigrationException(
            scripts: $scripts,
            message: 'Migration failed'
        );
        $this->migrationFileManager->expects($this->once())
            ->method('getMigrations')
            ->willReturn($allMigrations)
        ;
        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([])
        ;
        $this->migrationExecutor
            ->method('execute')
            ->willThrowException($migrationException)
        ;
        $this->rollbackExecutor->method('rollback');

        $this->service->execute($basePath);
    }

    public function testExecuteCallsRollbackExecutorWithScriptsFromException(): void
    {
        $basePath = '/migrations';
        $script1 = Script::build('001_create_table.sql');
        $script2 = Script::build('002_add_column.sql');
        $scripts = [$script1, $script2];
        $migration = Migration::new(name: '20240115', scripts: $scripts);
        $allMigrations = [$migration];
        $migrationException = new MigrationException(
            scripts: $scripts,
            message: 'Migration failed'
        );
        $this->migrationFileManager->expects($this->once())->method('getMigrations')->willReturn($allMigrations);
        $this->migrationRepository->expects($this->once())->method('getMigrations')->willReturn([]);
        $this->migrationExecutor->method('execute')->willThrowException($migrationException);
        $this->rollbackExecutor->method('rollback');

        $this->service->execute($basePath);
    }

    public function testExecuteLogsRollbackCompletedSuccessfullyAfterRollback(): void
    {
        $basePath = '/migrations';
        $script = Script::build('001_create_table.sql');
        $scripts = [$script];
        $migration = Migration::new(name: '20240115', scripts: $scripts);
        $allMigrations = [$migration];
        $migrationException = new MigrationException(
            scripts: $scripts,
            message: 'Migration failed'
        );
        $this->migrationFileManager->expects($this->once())->method('getMigrations')->willReturn($allMigrations);
        $this->migrationRepository->expects($this->once())->method('getMigrations')->willReturn([]);
        $this->migrationExecutor->method('execute')->willThrowException($migrationException);
        $this->rollbackExecutor->method('rollback');

        $this->service->execute($basePath);
    }
}
