<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Repositories;

use AlfonsoSG\Mvc\Migrations\Domain\Entities\Migration;

interface MigrationRepository
{
    public function save(Migration $migration): void;

    /**
     * @return array<Migration>
     */
    public function getMigrations(): array;
}
