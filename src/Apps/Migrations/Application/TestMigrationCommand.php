<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Application;

final readonly class TestMigrationCommand
{
    public function __construct(
        public string $migrationName,
        public string $basePath,
    ) {}
}
