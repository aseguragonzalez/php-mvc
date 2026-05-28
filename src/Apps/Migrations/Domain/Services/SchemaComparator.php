<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

use PhpMvc\Migrations\Domain\ValueObjects\SchemaSnapshot;

interface SchemaComparator
{
    public function compare(SchemaSnapshot $initial, SchemaSnapshot $final): SchemaComparisonResult;
}
