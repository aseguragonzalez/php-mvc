<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Application;

interface RunMigrations
{
    public function execute(string $basePath): void;
}
