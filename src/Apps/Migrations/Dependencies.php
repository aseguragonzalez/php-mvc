<?php

declare(strict_types=1);

namespace PhpMvc\Migrations;

use PhpMvc\Files\DefaultFileManager;
use PhpMvc\Files\FileManager;
use PhpMvc\Migrations\Application\RunMigrations;
use PhpMvc\Migrations\Application\RunMigrationsHandler;
use PhpMvc\Migrations\Application\TestMigration;
use PhpMvc\Migrations\Application\TestMigrationHandler;
use PhpMvc\Migrations\Domain\Clients\DbClient;
use PhpMvc\Migrations\Domain\Repositories\MigrationRepository;
use PhpMvc\Migrations\Domain\Services\DatabaseBackupManager;
use PhpMvc\Migrations\Domain\Services\MigrationExecutor;
use PhpMvc\Migrations\Domain\Services\MigrationExecutorHandler;
use PhpMvc\Migrations\Domain\Services\MigrationFileManager;
use PhpMvc\Migrations\Domain\Services\MigrationFileManagerHandler;
use PhpMvc\Migrations\Domain\Services\MigrationTestScopeFactory;
use PhpMvc\Migrations\Domain\Services\RollbackExecutor;
use PhpMvc\Migrations\Domain\Services\RollbackExecutorHandler;
use PhpMvc\Migrations\Domain\Services\SchemaComparator;
use PhpMvc\Migrations\Domain\Services\SchemaComparatorHandler;
use PhpMvc\Migrations\Domain\Services\SchemaSnapshotExecutor;
use PhpMvc\Migrations\Domain\Services\TestMigrationExecutor;
use PhpMvc\Migrations\Domain\Services\TestMigrationExecutorHandler;
use PhpMvc\Migrations\Infrastructure\MigrationTestScopeFactoryHandler;
use PhpMvc\Migrations\Infrastructure\ShellDatabaseBackupManager;
use PhpMvc\Migrations\Infrastructure\SqlDbClient;
use PhpMvc\Migrations\Infrastructure\SqlMigrationRepository;
use PhpMvc\Migrations\Infrastructure\SqlSchemaSnapshotExecutor;
use PhpMvc\MutableContainerInterface;

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
