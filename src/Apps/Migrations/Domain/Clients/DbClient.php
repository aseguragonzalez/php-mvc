<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Clients;

interface DbClient
{
    public function beginTransaction(): void;

    public function commit(): void;

    /**
     * @param array<string> $statements SQL statements to execute
     */
    public function execute(array $statements): void;

    public function inTransaction(): bool;

    public function rollBack(): void;

    public function useDatabase(string $database): void;
}
