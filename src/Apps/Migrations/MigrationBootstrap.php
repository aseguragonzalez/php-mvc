<?php

declare(strict_types=1);

namespace PhpMvc\Migrations;

use PhpMvc\LoggerSettings;
use PhpMvc\MutableContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class MigrationBootstrap
{
    public static function registerFromEnvironment(MutableContainerInterface $container): void
    {
        $container->set(
            LoggerSettings::class,
            new LoggerSettings(
                environment: getenv('ENVIRONMENT') ?: 'local',
                serviceName: getenv('MIGRATIONS_SERVICE_NAME') ?: 'migrations',
                serviceVersion: getenv('MIGRATIONS_SERVICE_VERSION') ?: '1.0.0',
                logLevel: getenv('MIGRATIONS_LOG_LEVEL') ?: 'debug',
            ),
        );
        $container->set(
            MigrationSettings::class,
            new MigrationSettings(
                host: getenv('MIGRATIONS_DATABASE_HOST') ?: 'localhost',
                database: getenv('MIGRATIONS_DATABASE_NAME') ?: 'migrations',
                user: getenv('MIGRATIONS_DATABASE_USER') ?: 'migrations',
                password: getenv('MIGRATIONS_DATABASE_PASSWORD') ?: '',
            ),
        );

        $container->set(LoggerInterface::class, new NullLogger());

        Dependencies::configure($container);
    }
}
