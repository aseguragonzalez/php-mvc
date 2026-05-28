<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Routes\Route;

use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Controllers\Controller;

final class RouteController extends Controller
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
