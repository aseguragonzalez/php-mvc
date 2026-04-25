<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Services;

interface MigrationTestScopeFactory
{
    public function createScope(string $databaseName): MigrationTestScope;
}
