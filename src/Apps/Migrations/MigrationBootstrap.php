<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations;

use AlfonsoSG\Mvc\LoggerSettings;
use DI\Container;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

final class MigrationBootstrap
{
    public static function registerFromEnvironment(Container $container): void
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

        /** @var LoggerSettings $loggerSettings */
        $loggerSettings = $container->get(LoggerSettings::class);

        $handler = new StreamHandler(
            stream: 'php://stdout',
            level: self::logLevelFromSettings($loggerSettings)
        );
        $handler->setFormatter(new LineFormatter(
            format: '[%datetime%] %level_name%: %message%',
            dateFormat: 'Y-m-d H:i:s',
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
            includeStacktraces: false,
        ));

        $logger = new Logger($loggerSettings->serviceName);
        $logger->pushHandler($handler);
        $logger->pushProcessor(new PsrLogMessageProcessor());

        $container->set(LoggerInterface::class, $logger);

        Dependencies::configure($container);
    }

    private static function logLevelFromSettings(LoggerSettings $loggerSettings): Level
    {
        $logLevel = $loggerSettings->logLevel;
        $logLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if (!in_array($logLevel, $logLevels)) {
            throw new \InvalidArgumentException("Invalid log level: {$logLevel}");
        }

        return Level::fromName($logLevel);
    }
}
