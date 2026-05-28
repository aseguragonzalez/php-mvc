<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Infrastructure;

use PhpMvc\BackgroundTasks\Domain\Repositories\TaskRepository;
use PhpMvc\BackgroundTasks\Domain\Task;

final readonly class SqlTaskRepository implements TaskRepository
{
    public function __construct(private \PDO $db) {}

    public function save(Task $task): void
    {
        $id = $task->id;
        $argumentsJson = json_encode($task->arguments, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
        $createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $processedAt = null !== $task->processedAt ? $task->processedAt->format('Y-m-d H:i:s') : null;

        $sql = <<<'SQL'
            INSERT INTO background_tasks (id, task_type, arguments, created_at, processed, processed_at)
            VALUES (:id, :task_type, :arguments, :created_at, :processed, :processed_at)
            ON DUPLICATE KEY UPDATE
                processed = VALUES(processed),
                processed_at = VALUES(processed_at)
            SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'task_type' => $task->taskType,
            'arguments' => $argumentsJson,
            'created_at' => $createdAt,
            'processed' => $task->processed ? 1 : 0,
            'processed_at' => $processedAt,
        ]);
    }

    /**
     * @return array<Task>
     */
    public function findPending(int $limit): array
    {
        $sql = <<<'SQL'
            SELECT id, task_type, arguments
            FROM background_tasks
            WHERE processed = 0
            ORDER BY created_at ASC
            LIMIT :limit
            SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array{id: string, task_type: string, arguments: array<string, mixed>|string}> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $raw = is_string($row['arguments'])
                ? json_decode($row['arguments'], true, 512, JSON_THROW_ON_ERROR)
                : $row['arguments'];
            if (!is_array($raw)) {
                throw new \RuntimeException(sprintf(
                    'Invalid arguments payload for background task "%s" of type "%s". Expected JSON array or object.',
                    $row['id'],
                    $row['task_type']
                ));
            }

            /** @var array<string, mixed> $arguments */
            $arguments = $raw;
            $result[] = Task::build(
                $row['id'],
                $row['task_type'],
                $arguments
            );
        }

        return $result;
    }
}
