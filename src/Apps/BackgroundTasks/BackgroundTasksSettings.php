<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks;

final readonly class BackgroundTasksSettings
{
    /**
     * @param array<string, string> $handlerMap Task type => TaskHandler class name
     */
    public function __construct(
        public string $host,
        public string $database,
        public string $user,
        public string $password,
        public array $handlerMap = [],
        public string $charset = 'utf8mb4',
        public int $pollIntervalSeconds = 0,
    ) {}

    public function getDsn(): string
    {
        return "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
    }
}
