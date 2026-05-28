<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Application\RegisterTask;

final readonly class RegisterTaskCommand
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public string $taskType,
        public array $arguments = [],
    ) {}
}
