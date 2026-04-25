<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\BackgroundTasks\Domain;

interface TransactionRunner
{
    public function runInTransaction(\Closure $operation): void;
}
