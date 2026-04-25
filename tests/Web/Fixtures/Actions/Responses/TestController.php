<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Fixtures\Actions\Responses;

use AlfonsoSG\Mvc\Actions\Responses\ActionResponse;
use AlfonsoSG\Mvc\Controllers\Controller;

final class TestController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): ActionResponse
    {
        return $this->view();
    }

    public function list(int $offset, int $limit): ActionResponse
    {
        $model = new \stdClass();
        $model->offset = $offset;
        $model->limit = $limit;

        return $this->view(model: $model);
    }

    public function search(int $offset, int $limit, SearchRequest $request): ActionResponse
    {
        $model = new \stdClass();
        $model->offset = $offset;
        $model->limit = $limit;
        $model->request = $request;

        return $this->view(model: $model);
    }
}
