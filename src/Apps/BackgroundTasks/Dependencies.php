<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks;

use AlfonsoSG\Mvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasks;
use AlfonsoSG\Mvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasksHandler;
use AlfonsoSG\Mvc\BackgroundTasks\Application\RegisterTask\RegisterTask;
use AlfonsoSG\Mvc\BackgroundTasks\Application\RegisterTask\RegisterTaskHandler;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\Repositories\TaskRepository;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskBus;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandlerRegistry;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TransactionRunner;
use AlfonsoSG\Mvc\BackgroundTasks\Infrastructure\MapTaskHandlerRegistry;
use AlfonsoSG\Mvc\BackgroundTasks\Infrastructure\PdoTransactionRunner;
use AlfonsoSG\Mvc\BackgroundTasks\Infrastructure\SqlTaskRepository;
use AlfonsoSG\Mvc\BackgroundTasks\Infrastructure\TaskBusHandler;
use DI\Container;

final class Dependencies
{
    public static function configure(Container $container): void
    {
        $container->set(TaskHandlerRegistry::class, $container->get(MapTaskHandlerRegistry::class));
        $container->set(TaskRepository::class, $container->get(SqlTaskRepository::class));
        $container->set(TransactionRunner::class, $container->get(PdoTransactionRunner::class));
        $container->set(RegisterTask::class, $container->get(RegisterTaskHandler::class));
        $container->set(TaskBus::class, $container->get(TaskBusHandler::class));
        $container->set(ProcessPendingTasks::class, $container->get(ProcessPendingTasksHandler::class));
    }
}
