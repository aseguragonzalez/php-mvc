<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Fixtures\Requests;

final class ListRequest
{
    public function __construct(
        public readonly int $id,
        public readonly int $offset,
        public readonly int $limit
    ) {}
}
