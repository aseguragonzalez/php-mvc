<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Actions\Responses\View;
use AlfonsoSG\Mvc\Requests\RequestContext;

interface ViewEngine
{
    public function render(View $view, RequestContext $context): string;
}
