<?php

namespace Tests\Unit\AlfonsoSG\Mvc\Actions\Responses\Views;

use AlfonsoSG\Mvc\Actions\Responses\View;
use AlfonsoSG\Mvc\Responses\Headers\ContentType;
use AlfonsoSG\Mvc\Responses\StatusCode;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ViewTest extends TestCase
{
    public function testCreateView(): void
    {
        $view = new View(
            viewPath: 'view_path',
            data: new \stdClass(),
            headers: [],
            statusCode: StatusCode::Ok
        );

        $this->assertSame('view_path', $view->viewPath);
        $this->assertIsObject($view->data);
        $this->assertSame(StatusCode::Ok, $view->statusCode);
        $this->assertCount(1, $view->headers);
        $header = $view->headers[0];
        $this->assertInstanceOf(ContentType::class, $header);
        $this->assertTrue($header->equals(ContentType::html()));
    }

    public function testCreateViewWithArrayData(): void
    {
        $view = new View(
            viewPath: 'view_path',
            data: ['key' => 'value'],
            headers: [],
            statusCode: StatusCode::Ok
        );

        $this->assertSame('view_path', $view->viewPath);
        $this->assertIsArray($view->data);
        $this->assertSame('value', $view->data['key']);
    }
}
