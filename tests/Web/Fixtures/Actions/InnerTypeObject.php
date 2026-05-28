<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Actions;

final class InnerTypeObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly \DateTimeImmutable $createdAt,
        public readonly bool $active,
    ) {}
}
