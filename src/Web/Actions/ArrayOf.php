<?php

declare(strict_types=1);

namespace PhpMvc\Actions;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class ArrayOf
{
    public function __construct(public readonly string $type) {}
}
