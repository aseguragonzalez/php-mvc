<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Actions\Responses;

use AlfonsoSG\Mvc\Actions\Responses\LocalRedirectTo;
use AlfonsoSG\Mvc\Responses\Headers\ContentType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Actions\Responses\SearchRequest;
use Tests\Unit\AlfonsoSG\Mvc\Fixtures\Actions\Responses\TestController;

/**
 * @internal
 *
 * @coversNothing
 */
final class LocalRedirectToTest extends TestCase
{
    public function testCreate(): void
    {
        $action = 'index';
        $expectedHeaders = [ContentType::html()];

        $response = LocalRedirectTo::create($action, TestController::class);

        $this->assertSame($action, $response->action);
        $this->assertSame(TestController::class, $response->controller);
        $this->assertEquals(new \stdClass(), $response->data);
        $this->assertEquals($expectedHeaders, $response->headers);
        $this->assertSame(303, $response->statusCode->value);
    }

    public function testCreateFailWhenActionDoesNotExists(): void
    {
        $action = 'fakeAction';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Action not found: {$action}");
        LocalRedirectTo::create($action, TestController::class);
    }

    public function testCreateFailWhenControllerDoesNotExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller does not exists: '.SearchRequest::class);
        LocalRedirectTo::create('index', SearchRequest::class);
    }

    public function testCreateFailWhenActionParametersAreRequired(): void
    {
        $action = 'list';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Action parameters for {$action} are required");
        LocalRedirectTo::create($action, TestController::class);
    }

    #[DataProvider('invalidArgsProvider')]
    public function testCreateFailWhenActionParametersDoesNotMatch(string $action, object $args): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Action parameters for {$action} does not match");
        LocalRedirectTo::create($action, TestController::class, $args);
    }

    /**
     * @return array<array{string, object}>
     */
    public static function invalidArgsProvider(): array
    {
        $withOtherProperty = new \stdClass();
        $withOtherProperty->other = 'other';
        $withOtherProperty->offset = 1;

        $withMissingProperty = new \stdClass();
        $withMissingProperty->offset = 1;

        $searchArgs = new \stdClass();
        $searchArgs->offset = 1;
        $searchArgs->limit = 10;

        return [
            ['list', new \stdClass()],
            ['list', $withOtherProperty],
            ['list', $withMissingProperty],
            ['search', $searchArgs],
        ];
    }

    #[DataProvider('validArgsProvider')]
    public function testCreateWithArgs(string $action, object $args): void
    {
        $expectedHeaders = [ContentType::html()];

        $response = LocalRedirectTo::create($action, TestController::class, $args);

        $this->assertSame($action, $response->action);
        $this->assertSame(TestController::class, $response->controller);
        $this->assertEquals(new \stdClass(), $response->data);
        $this->assertEquals($expectedHeaders, $response->headers);
        $this->assertSame(303, $response->statusCode->value);
        $this->assertEquals($args, $response->args);
    }

    /**
     * @return array<array{string, object}>
     */
    public static function validArgsProvider(): array
    {
        $listArgs = new \stdClass();
        $listArgs->offset = 1;
        $listArgs->limit = 10;

        $searchRequest = new \stdClass();
        $searchRequest->name = 'name';
        $searchRequest->email = 'email';
        $searchArgs = new \stdClass();
        $searchArgs->offset = 1;
        $searchArgs->limit = 10;
        $searchArgs->request = $searchRequest;

        return [
            ['list', $listArgs],
            ['search', $searchArgs],
        ];
    }
}
