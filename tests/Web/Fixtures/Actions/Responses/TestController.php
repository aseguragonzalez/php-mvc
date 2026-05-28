<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Actions\Responses;

use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Controllers\Controller;

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
