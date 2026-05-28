<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks;

use PhpMvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasks;
use PhpMvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasksHandler;
use PhpMvc\BackgroundTasks\Application\RegisterTask\RegisterTask;
use PhpMvc\BackgroundTasks\Application\RegisterTask\RegisterTaskHandler;
use PhpMvc\BackgroundTasks\Domain\Repositories\TaskRepository;
use PhpMvc\BackgroundTasks\Domain\TaskBus;
use PhpMvc\BackgroundTasks\Domain\TaskHandlerRegistry;
use PhpMvc\BackgroundTasks\Domain\TransactionRunner;
use PhpMvc\BackgroundTasks\Infrastructure\MapTaskHandlerRegistry;
use PhpMvc\BackgroundTasks\Infrastructure\PdoTransactionRunner;
use PhpMvc\BackgroundTasks\Infrastructure\SqlTaskRepository;
use PhpMvc\BackgroundTasks\Infrastructure\TaskBusHandler;
use PhpMvc\MutableContainerInterface;

final class BackgroundTasksBootstrap
{
    public static function configure(MutableContainerInterface $container): void
    {
        $container->set(TaskHandlerRegistry::class, $container->get(MapTaskHandlerRegistry::class));
        $container->set(TaskRepository::class, $container->get(SqlTaskRepository::class));
        $container->set(TransactionRunner::class, $container->get(PdoTransactionRunner::class));
        $container->set(RegisterTask::class, $container->get(RegisterTaskHandler::class));
        $container->set(TaskBus::class, $container->get(TaskBusHandler::class));
        $container->set(ProcessPendingTasks::class, $container->get(ProcessPendingTasksHandler::class));
    }
}
