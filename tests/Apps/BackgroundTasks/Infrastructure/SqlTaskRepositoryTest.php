<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\BackgroundTasks\Infrastructure;

use AlfonsoSG\Mvc\BackgroundTasks\Domain\Task;
use AlfonsoSG\Mvc\BackgroundTasks\Infrastructure\SqlTaskRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlTaskRepositoryTest extends TestCase
{
    private MockObject&\PDO $pdo;
    private MockObject&\PDOStatement $statement;
    private SqlTaskRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->statement = $this->createMock(\PDOStatement::class);
        $this->repository = new SqlTaskRepository($this->pdo);
    }

    public function testSaveInsertsTaskWithTypeAndArguments(): void
    {
        $task = Task::new('send_notification', ['user_id' => '123', 'template' => 'welcome']);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO background_tasks'))
            ->willReturn($this->statement)
        ;

        $this->statement->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params): bool {
                $createdAt = $params['created_at'] ?? null;

                return isset(
                    $params['id'],
                    $params['task_type'],
                    $params['arguments'],
                    $params['created_at'],
                    $params['processed']
                )
                    && array_key_exists('processed_at', $params)
                    && 'send_notification' === $params['task_type']
                    && '{"user_id":"123","template":"welcome"}' === $params['arguments']
                    && 0 === $params['processed']
                    && null === $params['processed_at']
                    && is_string($params['id'])
                    && strlen($params['id']) > 0
                    && is_string($createdAt)
                    && 1 === preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $createdAt);
            }))
            ->willReturn(true)
        ;

        $this->repository->save($task);
    }

    public function testSaveWithEmptyArgumentsInsertsEmptyJson(): void
    {
        $task = Task::new('no_op', []);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement)
        ;

        $this->statement->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params): bool {
                return '{}' === $params['arguments']
                    && 'no_op' === $params['task_type']
                    && 0 === $params['processed']
                    && null === $params['processed_at'];
            }))
            ->willReturn(true)
        ;

        $this->repository->save($task);
    }

    public function testSaveWithScalarArgumentValues(): void
    {
        $arguments = [
            'name' => 'test',
            'count' => 42,
            'ratio' => 3.14,
            'active' => true,
            'nullable' => null,
        ];
        $task = Task::new('scalar_task', $arguments);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement)
        ;

        $this->statement->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) use ($arguments): bool {
                $args = $params['arguments'];
                if (!is_string($args)) {
                    return false;
                }
                $decoded = json_decode($args, true);

                return $decoded === $arguments
                    && 0 === $params['processed']
                    && null === $params['processed_at'];
            }))
            ->willReturn(true)
        ;

        $this->repository->save($task);
    }

    public function testSaveWithNestedArgumentsStoresCorrectJson(): void
    {
        $arguments = ['a' => ['b' => 1, 'c' => ['d' => 'x']]];
        $task = Task::new('nested_task', $arguments);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement)
        ;

        $this->statement->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) use ($arguments): bool {
                $args = $params['arguments'];
                if (!is_string($args)) {
                    return false;
                }
                $decoded = json_decode($args, true);

                return $decoded === $arguments
                    && 'nested_task' === $params['task_type']
                    && 0 === $params['processed']
                    && null === $params['processed_at'];
            }))
            ->willReturn(true)
        ;

        $this->repository->save($task);
    }

    public function testFindPendingQueriesUnprocessedTasksOrderedByCreatedAt(): void
    {
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE processed = 0'))
            ->willReturn($this->statement)
        ;
        $this->statement->expects($this->once())
            ->method('bindValue')
            ->with('limit', 10, \PDO::PARAM_INT)
        ;
        $this->statement->expects($this->once())
            ->method('execute')
        ;
        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 'task-1',
                    'task_type' => 'send_email',
                    'arguments' => '{"to":"user@example.com"}',
                ],
            ])
        ;

        $result = $this->repository->findPending(10);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Task::class, $result[0]);
        $this->assertSame('task-1', $result[0]->id);
        $this->assertSame('send_email', $result[0]->taskType);
        $this->assertSame(['to' => 'user@example.com'], $result[0]->arguments);
        $this->assertFalse($result[0]->processed);
    }

    public function testFindPendingReturnsEmptyArrayWhenNoRows(): void
    {
        $this->pdo->expects($this->once())->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('bindValue');
        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->once())->method('fetchAll')->willReturn([]);

        $result = $this->repository->findPending(5);

        $this->assertSame([], $result);
    }

    public function testSaveProcessedTaskUpdatesRow(): void
    {
        $task = Task::build('task-123', 'dummy', [])->markAsProcessed();

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ON DUPLICATE KEY UPDATE'))
            ->willReturn($this->statement)
        ;
        $this->statement->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params): bool {
                return 'task-123' === $params['id']
                    && isset($params['processed_at'])
                    && is_string($params['processed_at'])
                    && 1 === preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $params['processed_at'])
                    && 1 === $params['processed'];
            }))
            ->willReturn(true)
        ;

        $this->repository->save($task);
    }
}
