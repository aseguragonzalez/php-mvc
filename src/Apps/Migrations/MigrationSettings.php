<?php

declare(strict_types=1);

namespace PhpMvc\Migrations;

final readonly class MigrationSettings
{
    public function __construct(
        public string $host,
        public string $database,
        public string $user,
        public string $password,
        public string $charset = 'utf8mb4',
    ) {}

    public function getDsn(): string
    {
        return "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
    }
}
