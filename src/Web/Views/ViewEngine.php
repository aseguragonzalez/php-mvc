<?php

declare(strict_types=1);

namespace PhpMvc\Views;

use PhpMvc\Actions\Responses\View;
use PhpMvc\Requests\RequestContext;

interface ViewEngine
{
    public function render(View $view, RequestContext $context): string;
}
