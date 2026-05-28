<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Infrastructure;

use PhpMvc\BackgroundTasks\Domain\Task;
use PhpMvc\BackgroundTasks\Domain\TaskBus;
use PhpMvc\BackgroundTasks\Domain\TaskHandlerRegistry;

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
