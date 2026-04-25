<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

interface Command
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @param array<string> $args
     */
    public function execute(array $args): int;
}
