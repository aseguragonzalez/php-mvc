<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Domain;

interface TaskHandler
{
    public function handle(Task $task): void;
}
