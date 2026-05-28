<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Application;

interface TestMigration
{
    public function execute(TestMigrationCommand $command): void;
}
