<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Application;

interface TestMigration
{
    public function execute(TestMigrationCommand $command): void;
}
