<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Domain;

interface TransactionRunner
{
    public function runInTransaction(\Closure $operation): void;
}
