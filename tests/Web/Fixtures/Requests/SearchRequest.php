<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Requests;

final class SearchRequest
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
    ) {}
}
