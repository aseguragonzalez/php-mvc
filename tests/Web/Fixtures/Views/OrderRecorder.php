<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Views;

/** Helper for pipeline test so PHPStan knows the order property. */
final class OrderRecorder
{
    /** @var array<int, int> */
    public array $order = [];
}
