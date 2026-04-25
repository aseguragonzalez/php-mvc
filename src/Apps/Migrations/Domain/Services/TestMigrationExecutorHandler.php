<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Services;

use AlfonsoSG\Mvc\Migrations\Domain\Clients\DbClient;
use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;

final readonly class TestMigrationExecutorHandler implements TestMigrationExecutor
{
    public function __construct(private DbClient $dbClient, private string $databaseName) {}

    public function execute(Migration $migration): void
    {
        foreach ($migration->scripts as $script) {
            $this->dbClient->useDatabase($this->databaseName);
            $this->dbClient->execute(statements: $script->getStatements());
        }
    }
}
