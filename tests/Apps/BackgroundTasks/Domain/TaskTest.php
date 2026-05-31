<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\BackgroundTasks\Domain;

use PhpMvc\BackgroundTasks\Domain\Task;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TaskTest extends TestCase
{
    public function testNewCreatesTaskWithTypeAndArguments(): void
    {
        $task = Task::new('send_email', ['to' => 'user@example.com']);

        $this->assertSame('send_email', $task->taskType);
        $this->assertSame(['to' => 'user@example.com'], $task->arguments);
    }

    public function testNewWithEmptyArguments(): void
    {
        $task = Task::new('no_op', []);

        $this->assertSame('no_op', $task->taskType);
        $this->assertSame([], $task->arguments);
    }

    public function testNewAcceptsNestedArguments(): void
    {
        $arguments = ['a' => ['b' => 1, 'c' => ['d' => 'x']]];
        $task = Task::new('nested_task', $arguments);

        $this->assertSame('nested_task', $task->taskType);
        $this->assertSame($arguments, $task->arguments);
    }

    public function testNewAcceptsNestedKeyValueDictionary(): void
    {
        $arguments = ['key' => ['nested' => 'array']];
        $task = Task::new('task', $arguments);

        $this->assertSame('task', $task->taskType);
        $this->assertSame($arguments, $task->arguments);
    }

    public function testNewThrowsWhenTaskTypeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task type must not be empty');

        Task::new('', ['key' => 'value']);
    }

    public function testNewThrowsWhenArgumentValueIsNotJsonSerializable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument values must be JSON-serializable');

        Task::new('bad_task', ['key' => new \stdClass()]);
    }

    public function testNewThrowsWhenArgumentKeyIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument keys must be non-empty strings');

        Task::new('task', ['' => 'value']);
    }

    public function testNewGeneratesIdAndSetsProcessedFalse(): void
    {
        $task = Task::new('no_op', []);

        $this->assertNotEmpty($task->id);
        $this->assertFalse($task->processed);
        $this->assertNull($task->processedAt);
    }

    public function testBuildThrowsWhenTaskTypeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Task::build('some-id', '', []);
    }

    public function testFromPersistenceCreatesTaskWithIdAndProcessedFalse(): void
    {
        $task = Task::build('id-123', 'send_email', ['to' => 'a@b.com']);

        $this->assertSame('id-123', $task->id);
        $this->assertSame('send_email', $task->taskType);
        $this->assertSame(['to' => 'a@b.com'], $task->arguments);
        $this->assertFalse($task->processed);
        $this->assertNull($task->processedAt);
    }

    public function testMarkAsProcessedReturnsTaskWithProcessedTrueAndSameId(): void
    {
        $task = Task::build('id-42', 'send_email', []);

        $processed = $task->markAsProcessed();

        $this->assertSame('id-42', $processed->id);
        $this->assertSame('send_email', $processed->taskType);
        $this->assertTrue($processed->processed);
        $this->assertInstanceOf(\DateTimeImmutable::class, $processed->processedAt);
    }

    public function testMarkAsProcessedOnNewTaskPreservesGeneratedId(): void
    {
        $task = Task::new('no_op', []);
        $processed = $task->markAsProcessed();

        $this->assertSame($task->id, $processed->id);
        $this->assertTrue($processed->processed);
    }
}
