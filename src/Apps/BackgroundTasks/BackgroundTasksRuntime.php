<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks;

use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandler;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandlerRegistry;
use DI\Container;

final class BackgroundTasksRuntime
{
    public static function register(Container $container): void
    {
        /** @var BackgroundTasksSettings $settings */
        $settings = $container->get(BackgroundTasksSettings::class);
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

        Dependencies::configure($container);

        /** @var TaskHandlerRegistry $registry */
        $registry = $container->get(TaskHandlerRegistry::class);
        foreach ($settings->handlerMap as $taskType => $handlerClass) {
            $handler = $container->get($handlerClass);

            if (!$handler instanceof TaskHandler) {
                $resolvedType = \is_object($handler) ? \get_class($handler) : \gettype($handler);

                throw new \RuntimeException(\sprintf(
                    'Handler for task type "%s" must implement %s, got %s',
                    (string) $taskType,
                    TaskHandler::class,
                    $resolvedType
                ));
            }
            $registry->register($taskType, $handler);
        }
    }
}
