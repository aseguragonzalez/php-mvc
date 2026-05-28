<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Requests;

final class FindRequest
{
    public function __construct(public readonly int $offset, public readonly int $limit) {}
}
