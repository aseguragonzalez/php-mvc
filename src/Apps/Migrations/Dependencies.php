<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations;

use AlfonsoSG\Mvc\Files\DefaultFileManager;
use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\Migrations\Application\RunMigrations;
use AlfonsoSG\Mvc\Migrations\Application\RunMigrationsHandler;
use AlfonsoSG\Mvc\Migrations\Application\TestMigration;
use AlfonsoSG\Mvc\Migrations\Application\TestMigrationHandler;
use AlfonsoSG\Mvc\Migrations\Domain\Clients\DbClient;
use AlfonsoSG\Mvc\Migrations\Domain\Repositories\MigrationRepository;
use AlfonsoSG\Mvc\Migrations\Domain\Services\DatabaseBackupManager;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationExecutorHandler;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationFileManager;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationFileManagerHandler;
use AlfonsoSG\Mvc\Migrations\Domain\Services\MigrationTestScopeFactory;
use AlfonsoSG\Mvc\Migrations\Domain\Services\RollbackExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\Services\RollbackExecutorHandler;
use AlfonsoSG\Mvc\Migrations\Domain\Services\SchemaComparator;
use AlfonsoSG\Mvc\Migrations\Domain\Services\SchemaComparatorHandler;
use AlfonsoSG\Mvc\Migrations\Domain\Services\SchemaSnapshotExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\Services\TestMigrationExecutor;
use AlfonsoSG\Mvc\Migrations\Domain\Services\TestMigrationExecutorHandler;
use AlfonsoSG\Mvc\Migrations\Infrastructure\MigrationTestScopeFactoryHandler;
use AlfonsoSG\Mvc\Migrations\Infrastructure\ShellDatabaseBackupManager;
use AlfonsoSG\Mvc\Migrations\Infrastructure\SqlDbClient;
use AlfonsoSG\Mvc\Migrations\Infrastructure\SqlMigrationRepository;
use AlfonsoSG\Mvc\Migrations\Infrastructure\SqlSchemaSnapshotExecutor;
use AlfonsoSG\Mvc\MutableContainerInterface;

final class Dependencies
{
    public static function configure(MutableContainerInterface $container): void
    {
        /** @var MigrationSettings $settings */
        $settings = $container->get(MigrationSettings::class);
        $connection = new \PDO(
            $settings->getDsn(),
            $settings->user,
            $settings->password,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
        $container->set(\PDO::class, $connection);

        // Infrastructure services
        $container->set(FileManager::class, $container->get(DefaultFileManager::class));
        $container->set(MigrationRepository::class, $container->get(SqlMigrationRepository::class));
        $container->set(DbClient::class, $container->get(SqlDbClient::class));

        /** @var MigrationRepository $repo */
        $repo = $container->get(MigrationRepository::class);
        /** @var DbClient $db */
        $db = $container->get(DbClient::class);

        $container->set(
            MigrationExecutorHandler::class,
            new MigrationExecutorHandler($repo, $db, $settings->database),
        );
        $container->set(MigrationExecutor::class, $container->get(MigrationExecutorHandler::class));
        $container->set(MigrationFileManager::class, $container->get(MigrationFileManagerHandler::class));
        $container->set(
            RollbackExecutorHandler::class,
            new RollbackExecutorHandler($db, $settings->database),
        );
        $container->set(RollbackExecutor::class, $container->get(RollbackExecutorHandler::class));
        $container->set(RunMigrations::class, $container->get(RunMigrationsHandler::class));
        $container->set(SchemaSnapshotExecutor::class, $container->get(SqlSchemaSnapshotExecutor::class));
        $container->set(SchemaComparator::class, $container->get(SchemaComparatorHandler::class));
        $container->set(
            TestMigrationExecutorHandler::class,
            new TestMigrationExecutorHandler($db, $settings->database),
        );
        $container->set(TestMigrationExecutor::class, $container->get(TestMigrationExecutorHandler::class));
        $container->set(DatabaseBackupManager::class, $container->get(ShellDatabaseBackupManager::class));
        $container->set(
            MigrationTestScopeFactory::class,
            $container->get(MigrationTestScopeFactoryHandler::class),
        );
        $container->set(TestMigration::class, $container->get(TestMigrationHandler::class));
    }
}
