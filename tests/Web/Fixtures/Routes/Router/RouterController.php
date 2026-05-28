<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Routes\Router;

use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Controllers\Controller;

final class RouterController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get(): ActionResponse
    {
        return $this->view();
    }
}
