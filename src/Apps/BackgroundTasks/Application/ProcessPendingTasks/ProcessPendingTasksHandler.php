<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Application\ProcessPendingTasks;

use PhpMvc\BackgroundTasks\Domain\Repositories\TaskRepository;
use PhpMvc\BackgroundTasks\Domain\TaskBus;
use PhpMvc\BackgroundTasks\Domain\TransactionRunner;
use Psr\Log\LoggerInterface;

final readonly class ProcessPendingTasksHandler implements ProcessPendingTasks
{
    public function __construct(
        private TaskRepository $taskRepository,
        private TaskBus $taskBus,
        private TransactionRunner $transactionRunner,
        private LoggerInterface $logger,
    ) {}

    public function execute(ProcessPendingTasksCommand $command): void
    {
        $tasks = $this->taskRepository->findPending($command->limit);

        foreach ($tasks as $task) {
            try {
                $this->transactionRunner->runInTransaction(function () use ($task): void {
                    $this->taskRepository->save($task->markAsProcessed());
                    $this->taskBus->dispatch($task);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process task', [
                    'taskId' => $task->id,
                    'taskType' => $task->taskType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
