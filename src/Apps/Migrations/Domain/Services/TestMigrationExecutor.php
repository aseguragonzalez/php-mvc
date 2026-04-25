<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Services;

use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;

interface TestMigrationExecutor
{
    public function execute(Migration $migration): void;
}
