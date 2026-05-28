<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

use PhpMvc\Migrations\Domain\Clients\DbClient;
use PhpMvc\Migrations\Domain\Entities\Migration;

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
