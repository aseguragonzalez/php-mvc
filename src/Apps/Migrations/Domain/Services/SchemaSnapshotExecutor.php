<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations\Domain\Services;

use AlfonsoSG\Mvc\Migrations\Domain\ValueObjects\SchemaSnapshot;

interface SchemaSnapshotExecutor
{
    public function capture(): SchemaSnapshot;
}
