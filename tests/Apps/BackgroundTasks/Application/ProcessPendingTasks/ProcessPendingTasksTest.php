<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\BackgroundTasks\Application\ProcessPendingTasks;

use AlfonsoSG\Mvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasks;
use AlfonsoSG\Mvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasksCommand;
use AlfonsoSG\Mvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasksHandler;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\Repositories\TaskRepository;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\Task;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TaskBus;
use AlfonsoSG\Mvc\BackgroundTasks\Domain\TransactionRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProcessPendingTasksTest extends TestCase
{
    private MockObject&TaskRepository $taskRepository;
    private MockObject&TaskBus $taskBus;
    private Stub&TransactionRunner $transactionRunner;
    private LoggerInterface $logger;
    private ProcessPendingTasks $service;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->taskBus = $this->createMock(TaskBus::class);
        $this->transactionRunner = $this->createStub(TransactionRunner::class);
        $this->transactionRunner->method('runInTransaction')->willReturnCallback(
            static function (\Closure $operation): void {
                $operation();
            }
        );
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->service = new ProcessPendingTasksHandler(
            $this->taskRepository,
            $this->taskBus,
            $this->transactionRunner,
            $this->logger,
        );
    }

    public function testExecuteFetchesPendingTasksAndDispatchesEachThenMarksProcessed(): void
    {
        $task = Task::build('id-1', 'send_email', ['to' => 'a@b.com']);

        $this->taskRepository->expects($this->once())
            ->method('findPending')
            ->with(50)
            ->willReturn([$task])
        ;

        $this->taskBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (Task $t): bool {
                return 'send_email' === $t->taskType && $t->arguments === ['to' => 'a@b.com'];
            }))
        ;

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Task $saved): bool {
                return 'id-1' === $saved->id
                    && true === $saved->processed
                    && $saved->processedAt instanceof \DateTimeImmutable;
            }))
        ;

        $this->service->execute(new ProcessPendingTasksCommand(limit: 50));
    }

    public function testExecuteProcessesMultiplePendingTasks(): void
    {
        $task1 = Task::build('id-1', 'type_a', []);
        $task2 = Task::build('id-2', 'type_b', ['x' => 1]);

        $this->taskRepository->expects($this->once())
            ->method('findPending')
            ->with(10)
            ->willReturn([$task1, $task2])
        ;

        $this->taskBus->expects($this->exactly(2))->method('dispatch');

        $recordedIds = [];
        $this->taskRepository->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (Task $subject) use (&$recordedIds): void {
                if ($subject->processed) {
                    $recordedIds[] = $subject->id;
                }
            })
        ;

        $this->service->execute(new ProcessPendingTasksCommand(limit: 10));

        $this->assertSame(['id-1', 'id-2'], $recordedIds);
    }

    public function testExecuteDoesNothingWhenNoPendingTasks(): void
    {
        $this->taskRepository->expects($this->once())
            ->method('findPending')
            ->with(100)
            ->willReturn([])
        ;

        $this->taskBus->expects($this->never())->method('dispatch');
        $this->taskRepository->expects($this->never())->method('save');

        $this->service->execute(new ProcessPendingTasksCommand(limit: 100));
    }

    public function testExecuteLogsErrorAndDoesNotMarkProcessedWhenDispatchThrows(): void
    {
        $task = Task::build('id-1', 'send_email', []);
        $this->taskRepository
            ->expects($this->once())
            ->method('findPending')
            ->willReturn([$task])
        ;
        $this->taskBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('Handler failed'))
        ;

        $this->taskRepository->expects($this->once())->method('save')->with(
            $this->callback(function (Task $saved): bool {
                return 'id-1' === $saved->id
                    && true === $saved->processed
                    && $saved->processedAt instanceof \DateTimeImmutable;
            }),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to process task',
                $this->callback(function (array $context): bool {
                    return isset($context['taskId'], $context['taskType'], $context['error'])
                        && 'id-1' === $context['taskId']
                        && 'send_email' === $context['taskType']
                        && 'Handler failed' === $context['error'];
                }),
            )
        ;

        $transactionRunner = $this->createStub(TransactionRunner::class);
        $transactionRunner->method('runInTransaction')->willReturnCallback(
            static function (\Closure $operation): void {
                $operation();
            }
        );
        $service = new ProcessPendingTasksHandler(
            $this->taskRepository,
            $this->taskBus,
            $transactionRunner,
            $logger,
        );
        $service->execute(new ProcessPendingTasksCommand(limit: 10));
    }
}
