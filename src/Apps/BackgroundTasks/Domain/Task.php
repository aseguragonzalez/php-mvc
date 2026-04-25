<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks\Domain;

readonly class Task
{
    /**
     * Key-value dictionary; values are JSON-serializable (scalars, null, or nested associative arrays).
     *
     * @param array<string, mixed> $arguments
     */
    protected function __construct(
        public string $id,
        public string $taskType,
        public array $arguments = [],
        public bool $processed = false,
        public ?\DateTimeImmutable $processedAt = null,
    ) {}

    /**
     * @param array<string, mixed> $arguments
     */
    public static function new(string $taskType, array $arguments = []): self
    {
        if ('' === $taskType) {
            throw new \InvalidArgumentException('Task type must not be empty');
        }
        self::validateArguments($arguments);

        return new self(
            id: uniqid('', true),
            taskType: $taskType,
            arguments: $arguments,
            processed: false,
            processedAt: null,
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function build(string $id, string $taskType, array $arguments): self
    {
        if ('' === $taskType) {
            throw new \InvalidArgumentException('Task type must not be empty');
        }
        self::validateArguments($arguments);

        return new self(
            id: $id,
            taskType: $taskType,
            arguments: $arguments,
            processed: false,
            processedAt: null,
        );
    }

    public function markAsProcessed(): self
    {
        return new self(
            id: $this->id,
            taskType: $this->taskType,
            arguments: $this->arguments,
            processed: true,
            processedAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    /**
     * Recursively validate: string keys (non-empty), values are scalars, null, or arrays.
     *
     * @param array<mixed, mixed> $arguments
     */
    private static function validateArguments(array $arguments): void
    {
        foreach ($arguments as $key => $value) {
            if (!is_string($key) || '' === $key) {
                throw new \InvalidArgumentException('Argument keys must be non-empty strings');
            }
            if (is_array($value)) {
                self::validateArguments($value);

                continue;
            }
            if (null !== $value && !is_scalar($value)) {
                throw new \InvalidArgumentException(
                    'Argument values must be JSON-serializable (scalars, null, or nested associative arrays)'
                );
            }
        }
    }
}
