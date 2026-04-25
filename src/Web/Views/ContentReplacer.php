<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Requests\RequestContext;

interface ContentReplacer
{
    /**
     * @param null|array<string, mixed>|object $model
     */
    public function replace(array|object|null $model, string $template, RequestContext $context): string;
}
