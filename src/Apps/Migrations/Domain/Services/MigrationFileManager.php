<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

use PhpMvc\Migrations\Domain\Entities\Migration;

interface MigrationFileManager
{
    /**
     * @return array<Migration>
     */
    public function getMigrations(string $basePath): array;

    public function getMigrationByName(string $basePath, string $migrationName): ?Migration;
}
