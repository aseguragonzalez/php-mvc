<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks\Domain;

interface TaskHandler
{
    public function handle(Task $task): void;
}
