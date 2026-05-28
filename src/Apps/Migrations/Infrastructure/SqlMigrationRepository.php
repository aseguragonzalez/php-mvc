<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Infrastructure;

use PhpMvc\Migrations\Domain\Entities\Migration;
use PhpMvc\Migrations\Domain\Entities\Script;
use PhpMvc\Migrations\Domain\Repositories\MigrationRepository;

final readonly class SqlMigrationRepository implements MigrationRepository
{
    public function __construct(private \PDO $db) {}

    public function save(Migration $migration): void
    {
        $sqlStatement = <<<'SQL'
            INSERT INTO migrations_history (
                migration,
                filename,
                created_at
            )
            VALUES (
                :migration,
                :filename,
                :created_at
            ) ON DUPLICATE KEY UPDATE
                migration = VALUES(migration),
                filename = VALUES(filename),
                created_at = VALUES(created_at)
            SQL;

        $data = $this->getDataFromMigration($migration);
        foreach ($data as $item) {
            $stmt = $this->db->prepare($sqlStatement);
            $stmt->execute($item);
        }
    }

    /**
     * @return array<Migration>
     */
    public function getMigrations(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM migrations_history ORDER BY migration, filename, created_at ASC');
        $stmt->execute();

        /** @var array<int, array{filename: string, created_at: string, migration: string}> $data */
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        /** @var array<string, array<int, array{filename: string, created_at: string, migration: string}>> $groupedData */
        $groupedData = [];
        foreach ($data as $item) {
            $groupedData[$item['migration']][] = $item;
        }

        $migrations = [];
        foreach ($groupedData as $migrationItems) {
            $firstItem = $migrationItems[0];
            $migration = Migration::build(
                name: $firstItem['migration'],
                createdAt: new \DateTimeImmutable($firstItem['created_at']),
                scripts: array_map(
                    fn ($item) => Script::build(fileName: $item['filename']),
                    $migrationItems
                )
            );
            $migrations[] = $migration;
        }

        return $migrations;
    }

    /**
     * @return array<array{filename: string, created_at: string, migration: string}>
     */
    private function getDataFromMigration(Migration $migration): array
    {
        return array_map(function (Script $script) use ($migration) {
            return [
                'created_at' => $migration->createdAt->format('Y-m-d H:i:s'),
                'filename' => $script->fileName,
                'migration' => $migration->name,
            ];
        }, $migration->scripts);
    }
}
