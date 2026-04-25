<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks\Domain;

interface TaskBus
{
    public function dispatch(Task $task): void;
}
