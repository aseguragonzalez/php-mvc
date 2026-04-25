<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Application;

interface RunMigrations
{
    public function execute(string $basePath): void;
}
