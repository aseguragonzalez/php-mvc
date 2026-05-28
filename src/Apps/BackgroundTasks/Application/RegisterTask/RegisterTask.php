<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Application\RegisterTask;

interface RegisterTask
{
    public function execute(RegisterTaskCommand $command): void;
}
