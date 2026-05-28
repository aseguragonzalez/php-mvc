<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Infrastructure;

use PhpMvc\BackgroundTasks\Domain\TransactionRunner;

final readonly class PdoTransactionRunner implements TransactionRunner
{
    public function __construct(private \PDO $db) {}

    public function runInTransaction(\Closure $operation): void
    {
        $this->db->beginTransaction();

        try {
            $operation();
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }
}
