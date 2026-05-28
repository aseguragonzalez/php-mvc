<?php

declare(strict_types=1);

namespace PhpMvc;

final readonly class ErrorMapping
{
    public function __construct(
        public int $statusCode,
        public string $templateName,
        public string $pageTitle
    ) {}
}
