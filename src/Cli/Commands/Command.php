<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

interface Command
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @param array<string> $args
     */
    public function execute(array $args): int;
}
