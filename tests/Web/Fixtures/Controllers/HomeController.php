<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Fixtures\Controllers;

use AlfonsoSG\Mvc\Actions\Responses\ActionResponse;
use AlfonsoSG\Mvc\Controllers\Controller;

final class HomeController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): ActionResponse
    {
        return $this->view();
    }
}
