<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Controllers;

use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Controllers\Controller;
use PhpMvc\Responses\Headers\Header;
use PhpMvc\Responses\StatusCode;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Unit\PhpMvc\Fixtures\Requests\EditRequest;
use Tests\Unit\PhpMvc\Fixtures\Requests\FindRequest;
use Tests\Unit\PhpMvc\Fixtures\Requests\ListRequest;
use Tests\Unit\PhpMvc\Fixtures\Requests\SearchRequest;

class TestController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): ActionResponse
    {
        return $this->view();
    }

    public function getDefaultView(): ActionResponse
    {
        return $this->view();
    }

    public function getCustomView(string $viewName): ActionResponse
    {
        return $this->view($viewName);
    }

    public function getCustomStatusCode(StatusCode $statusCode): ActionResponse
    {
        return $this->view(statusCode: $statusCode);
    }

    public function getCustomModel(object $model): ActionResponse
    {
        return $this->view(model: $model);
    }

    public function customRedirectToAction(string $action, object $args): ActionResponse
    {
        return $this->redirectToAction($action, args: $args);
    }

    /**
     * @param class-string $controller
     */
    public function redirectToControllerAction(string $controller, string $action, object $args): ActionResponse
    {
        return $this->redirectToAction($action, $controller, $args);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function customRedirectToUrl(string $url, array $args): ActionResponse
    {
        return $this->redirectTo(url: $url, args: $args);
    }

    public function customHeader(Header $header): ActionResponse
    {
        $this->addHeader($header);

        return $this->view();
    }

    public function custom(int $id, float $amount, string $name): ActionResponse
    {
        $model = new \stdClass();
        $model->id = $id;
        $model->amount = $amount;
        $model->name = $name;

        return $this->view(model: $model);
    }

    public function failed(): \stdClass
    {
        return new \stdClass();
    }

    public function redirect(): ActionResponse
    {
        return $this->redirectTo('http://test.com');
    }

    public function get(int $offset, int $limit): ActionResponse
    {
        $model = new \stdClass();
        $model->offset = $offset;
        $model->limit = $limit;

        return $this->view(model: $model);
    }

    public function getWithOptionals(int $offset = 10, int $limit = 20): ActionResponse
    {
        $model = new \stdClass();
        $model->offset = $offset;
        $model->limit = $limit;

        return $this->view(name: 'get', model: $model);
    }

    public function search(int $offset, int $limit, SearchRequest $request): ActionResponse
    {
        $model = new \stdClass();
        $model->offset = $offset;
        $model->limit = $limit;
        $model->name = $request->name;
        $model->email = $request->email;

        return $this->view(model: $model);
    }

    public function find(FindRequest $request): ActionResponse
    {
        return $this->view(model: $request);
    }

    public function list(ListRequest $request): ActionResponse
    {
        return $this->view(model: $request);
    }

    public function edit(EditRequest $request): ActionResponse
    {
        return $this->view(model: $request);
    }

    public function save(EditRequest $request, FindRequest $query): ActionResponse
    {
        $model = new \stdClass();
        $model->name = $request->name;
        $model->email = $request->email;
        $model->id = $request->id;
        $model->offset = $query->offset;
        $model->limit = $query->limit;

        return $this->view(model: $model);
    }

    public function localRedirect(int $offset, int $limit): ActionResponse
    {
        return $this->redirectToAction('get', args: (object) [
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    public function failedLocalRedirect(): ActionResponse
    {
        return $this->redirectToAction('failedLocalRedirectTarget');
    }

    public function failedLocalRedirectTarget(): ActionResponse
    {
        return $this->view();
    }

    public function delete(): ActionResponse
    {
        return $this->view();
    }

    public function getFromRequest(ServerRequestInterface $request): ActionResponse
    {
        $model = new \stdClass();
        $model->queryParams = $request->getQueryParams();
        $model->parsedBody = $request->getParsedBody();

        return $this->view('index', model: $model);
    }
}
