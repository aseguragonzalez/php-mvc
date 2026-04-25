<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks\Infrastructure;

use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandler;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskHandlerRegistry;

final class MapTaskHandlerRegistry implements TaskHandlerRegistry
{
    /**
     * @var array<string, TaskHandler>
     */
    private array $handlers = [];

    public function register(string $taskType, TaskHandler $handler): void
    {
        $this->handlers[$taskType] = $handler;
    }

    public function getHandler(string $taskType): ?TaskHandler
    {
        return $this->handlers[$taskType] ?? null;
    }
}
