<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Fixtures\Routes\Route;

use AlfonsoSG\Mvc\Actions\Responses\ActionResponse;
use AlfonsoSG\Mvc\Controllers\Controller;

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
