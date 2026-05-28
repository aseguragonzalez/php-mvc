<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Controllers;

use PhpMvc\Actions\Responses\ActionResponse;

final class SubTestController extends TestController
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
