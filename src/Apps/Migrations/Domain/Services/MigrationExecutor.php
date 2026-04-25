<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Services;

use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;

interface MigrationExecutor
{
    public function execute(Migration $migration): void;
}
