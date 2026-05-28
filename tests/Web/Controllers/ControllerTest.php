<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Controllers;

use PhpMvc\Actions\Responses\LocalRedirectTo;
use PhpMvc\Actions\Responses\View;
use PhpMvc\Responses\Headers\AccessControlAllowMethods;
use PhpMvc\Responses\Headers\ContentType;
use PhpMvc\Responses\Headers\Location;
use PhpMvc\Responses\StatusCode;
use PHPUnit\Framework\TestCase;
use Tests\Unit\PhpMvc\Fixtures\Controllers\HomeController;
use Tests\Unit\PhpMvc\Fixtures\Controllers\SubTestController;
use Tests\Unit\PhpMvc\Fixtures\Controllers\TestController;

/**
 * @internal
 *
 * @coversNothing
 */
final class ControllerTest extends TestCase
{
    private TestController $controller;

    protected function setUp(): void
    {
        $this->controller = new TestController();
    }

    protected function tearDown(): void {}

    public function testGetDefaultView(): void
    {
        $view = $this->controller->getDefaultView();

        $this->assertSame(StatusCode::Ok, $view->statusCode);
        $this->assertEquals([ContentType::html()], $view->headers);
        $this->assertInstanceOf(\stdClass::class, $view->data);
        if ($view instanceof View) {
            $this->assertSame('Test/getDefaultView', $view->viewPath);
        }
    }

    public function testGetViewByName(): void
    {
        $viewName = 'index';

        $view = $this->controller->getCustomView($viewName);

        if ($view instanceof View) {
            $this->assertSame("Test/{$viewName}", $view->viewPath);
        }
    }

    public function testGetViewWithStatusCode(): void
    {
        $statusCode = StatusCode::NotFound;

        $view = $this->controller->getCustomStatusCode($statusCode);

        $this->assertSame($statusCode, $view->statusCode);
    }

    public function testGetViewWithModel(): void
    {
        $model = new \stdClass();
        $model->name = 'John Doe';

        $view = $this->controller->getCustomModel($model);

        $this->assertSame($model, $view->data);
    }

    public function testRedirectToAction(): void
    {
        $action = 'index';
        $args = new \stdClass();
        $args->offset = 1;
        $args->limit = 10;

        $response = $this->controller->customRedirectToAction($action, $args);

        if ($response instanceof LocalRedirectTo) {
            $this->assertSame(TestController::class, $response->controller);
            $this->assertSame($action, $response->action);
            $this->assertSame($args, $response->args);
        }
    }

    public function testRedirectToActionWithController(): void
    {
        $action = 'index';
        $args = new \stdClass();
        $args->offset = 1;
        $args->limit = 10;

        $response = $this->controller->redirectToControllerAction(
            controller: HomeController::class,
            action: $action,
            args: $args
        );

        if ($response instanceof LocalRedirectTo) {
            $this->assertSame(HomeController::class, $response->controller);
            $this->assertSame($action, $response->action);
            $this->assertSame($args, $response->args);
        }
    }

    public function testRedirectToUrl(): void
    {
        $url = 'https://example.com';
        $args = ['offset' => 1, 'limit' => 10];

        $response = $this->controller->customRedirectToUrl($url, $args);

        $this->assertSame(StatusCode::Found, $response->statusCode);
        $this->assertEquals(
            [Location::toUrl("{$url}?offset=1&limit=10"), ContentType::html()],
            $response->headers
        );
    }

    public function testAddHeaders(): void
    {
        $header = new AccessControlAllowMethods(
            get: true,
            post: true,
            put: false,
            delete: false,
            options: false,
            head: false,
            patch: false,
            connect: false,
            trace: false
        );

        $response = $this->controller->customHeader($header);

        $this->assertSame(StatusCode::Ok, $response->statusCode);
        $this->assertEquals([$header, ContentType::html()], $response->headers);
    }

    public function testResolveSubClassViews(): void
    {
        $controller = new SubTestController();

        $view = $controller->index();

        $this->assertSame(StatusCode::Ok, $view->statusCode);
        $this->assertEquals([ContentType::html()], $view->headers);
        $this->assertInstanceOf(\stdClass::class, $view->data);
        if ($view instanceof View) {
            $this->assertSame('SubTest/index', $view->viewPath);
        }
    }
}
