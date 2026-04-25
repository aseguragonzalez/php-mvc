<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Fixtures\Routes\Router;

use AlfonsoSG\Mvc\Actions\Responses\ActionResponse;
use AlfonsoSG\Mvc\Controllers\Controller;

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
