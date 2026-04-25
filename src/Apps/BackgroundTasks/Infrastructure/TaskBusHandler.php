<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks\Infrastructure;

use AlfonsoSG\Mvc\BackgroundTasks\Domain\Task;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskBus;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandlerRegistry;

final readonly class TaskBusHandler implements TaskBus
{
    public function __construct(private TaskHandlerRegistry $registry) {}

    public function dispatch(Task $task): void
    {
        $handler = $this->registry->getHandler($task->taskType);
        if (null === $handler) {
            return;
        }
        $handler->handle($task);
    }
}
