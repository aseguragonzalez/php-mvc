<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Domain\Repositories;

use PhpMvc\BackgroundTasks\Domain\Task;

interface TaskRepository
{
    public function save(Task $task): void;

    /**
     * @return array<Task>
     */
    public function findPending(int $limit): array;
}
