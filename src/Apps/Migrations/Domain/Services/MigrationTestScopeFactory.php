<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

interface MigrationTestScopeFactory
{
    public function createScope(string $databaseName): MigrationTestScope;
}
