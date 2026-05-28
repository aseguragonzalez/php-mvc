<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

use PhpMvc\Migrations\Domain\Entities\Migration;

interface MigrationExecutor
{
    public function execute(Migration $migration): void;
}
