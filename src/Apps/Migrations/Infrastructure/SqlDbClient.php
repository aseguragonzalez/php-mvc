<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Infrastructure;

use PhpMvc\Migrations\Domain\Clients\DbClient;

final readonly class SqlDbClient implements DbClient
{
    public function __construct(private \PDO $db) {}

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * @param array<string> $statements SQL statements to execute
     */
    public function execute(array $statements): void
    {
        foreach ($statements as $statement) {
            $this->db->exec($statement);
        }
    }

    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }

    public function useDatabase(string $database): void
    {
        $escaped = '`'.str_replace('`', '``', $database).'`';
        $this->db->exec('USE '.$escaped);
    }
}
