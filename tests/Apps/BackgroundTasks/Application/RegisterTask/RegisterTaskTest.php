<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\BackgroundTasks\Application\RegisterTask;

use PhpMvc\BackgroundTasks\Application\RegisterTask\RegisterTask;
use PhpMvc\BackgroundTasks\Application\RegisterTask\RegisterTaskCommand;
use PhpMvc\BackgroundTasks\Application\RegisterTask\RegisterTaskHandler;
use PhpMvc\BackgroundTasks\Domain\Repositories\TaskRepository;
use PhpMvc\BackgroundTasks\Domain\Task;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RegisterTaskTest extends TestCase
{
    private MockObject&TaskRepository $taskRepository;
    private RegisterTask $service;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->service = new RegisterTaskHandler($this->taskRepository);
    }

    public function testExecuteSavesTaskWithCommandTypeAndArguments(): void
    {
        $command = new RegisterTaskCommand('send_email', ['to' => 'user@example.com']);

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Task $task): bool {
                return 'send_email' === $task->taskType
                    && $task->arguments === ['to' => 'user@example.com'];
            }))
        ;

        $this->service->execute($command);
    }

    public function testExecuteSavesTaskWithNestedArguments(): void
    {
        $arguments = ['a' => ['b' => 1, 'c' => ['d' => 'x']]];
        $command = new RegisterTaskCommand('nested_task', $arguments);

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Task $task) use ($arguments): bool {
                return 'nested_task' === $task->taskType
                    && $task->arguments === $arguments;
            }))
        ;

        $this->service->execute($command);
    }
}
