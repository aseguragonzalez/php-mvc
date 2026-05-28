<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Repositories;

use PhpMvc\Migrations\Domain\Entities\Migration;

interface MigrationRepository
{
    public function save(Migration $migration): void;

    /**
     * @return array<Migration>
     */
    public function getMigrations(): array;
}
