<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Views;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Actions\Responses\View;
use PhpMvc\Files\FileManager;
use PhpMvc\HtmlViewEngineSettings;
use PhpMvc\LanguageSettings;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Requests\RequestContextKeys;
use PhpMvc\Responses\StatusCode;
use PhpMvc\Security\Identity;
use PhpMvc\UiAssetsSettings;
use PhpMvc\Views\BranchesReplacer;
use PhpMvc\Views\ContentReplacer;
use PhpMvc\Views\ContentReplacerPipeline;
use PhpMvc\Views\HtmlViewEngine;
use PhpMvc\Views\I18nReplacer;
use PhpMvc\Views\ModelReplacer;
use PhpMvc\Views\ViewValueResolver;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Tests\Unit\PhpMvc\Fixtures\Views\BranchModel;

/**
 * @internal
 *
 * @coversNothing
 */
final class HtmlViewEngineTest extends TestCase
{
    private string $basePath = __DIR__.'/Files/';
    private FileManager&Stub $fileManager;
    private HtmlViewEngine $viewEngine;

    protected function setUp(): void
    {
        $this->fileManager = $this->createStub(FileManager::class);
        $resolver = new ViewValueResolver();
        $pipeline = new ContentReplacerPipeline([
            new ModelReplacer($resolver),
            new BranchesReplacer($resolver),
            new I18nReplacer(new LanguageSettings(basePath: __DIR__), $this->fileManager),
        ]);
        $settings = new HtmlViewEngineSettings(basePath: __DIR__, viewPath: '/Files');
        $this->viewEngine = new HtmlViewEngine(settings: $settings, contentReplacer: $pipeline);
    }

    public function testRenderInjectsUiAssetsSettingsPlaceholders(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);

        $uiAssets = new UiAssetsSettings(
            jsAssetsPathUrl: '/assets/scripts',
            mainJsBundler: 'main.min.js',
            cssAssetsPathUrl: '/assets/styles',
            mainCssBundler: 'main.min.css',
        );

        $viewEngine = new HtmlViewEngine(
            settings: new HtmlViewEngineSettings(basePath: __DIR__, viewPath: '/Files'),
            contentReplacer: new ContentReplacerPipeline([
                new ModelReplacer(new ViewValueResolver()),
                new BranchesReplacer(new ViewValueResolver()),
                new I18nReplacer(new LanguageSettings(basePath: __DIR__), $this->fileManager),
            ]),
            uiAssetsSettings: $uiAssets,
        );

        $view = new View(
            viewPath: 'ui_assets_placeholders',
            data: null,
            headers: [],
            statusCode: StatusCode::Ok
        );

        $expected = file_get_contents("{$this->basePath}/ui_assets_placeholders_expected.html");
        \assert(\is_string($expected));

        $body = $viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderFailWhenViewDoesNotExist(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $view = new View(
            viewPath: 'fake_view',
            data: null,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Template\s+not\s+found:\s+.*\/fake_view\.html$/');

        $this->viewEngine->render($view, new RequestContext());
    }

    public function testRenderWithPrimitiveProperties(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $model = new \stdClass();
        $model->name = 'Peter Parker';
        $model->age = 25;
        $model->height = 1.75;
        $model->isStudent = true;
        $model->isEmployed = false;
        $model->createdAt = new \DateTimeImmutable('2025-01-02T12:01:02.000Z');
        $data = new \stdClass();
        $data->model = $model;
        $view = new View(
            viewPath: 'primitive_properties',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/primitive_properties_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderWithArrayViewData(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $data = [
            'model' => [
                'name' => 'Peter Parker',
                'age' => 25,
                'height' => 1.75,
                'isStudent' => true,
                'isEmployed' => false,
                'createdAt' => new \DateTimeImmutable('2025-01-02T12:01:02.000Z'),
            ],
        ];
        $view = new View(
            viewPath: 'primitive_properties',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/primitive_properties_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderObjectProperties(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $address = new \stdClass();
        $address->street = 'Elm Street';
        $address->number = 123;
        $address->city = 'Springwood';
        $address->state = 'Ohio';
        $address->postalCode = '12345-678';
        $address->updatedAt = new \DateTimeImmutable('2025-01-03T12:01:02.000Z');
        $model = new \stdClass();
        $model->name = 'Freddy Krueger';
        $model->age = 45;
        $model->height = 1.75;
        $model->address = $address;
        $data = new \stdClass();
        $data->model = $model;
        $view = new View(
            viewPath: 'object_properties',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/object_properties_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderArrayOfObjects(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $user1 = new \stdClass();
        $user1->id = '1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p';
        $user1->name = 'Peter Parker';
        $user1->age = 25;
        $user2 = new \stdClass();
        $user2->id = '2b3c4d5e-6f7g-8h9i-0j1k-2l3m4n5o6p7q';
        $user2->name = 'Freddy Mercury';
        $user2->age = 45;
        $model = new \stdClass();
        $model->users = [$user1, $user2];
        $data = new \stdClass();
        $data->model = $model;
        $view = new View(
            viewPath: 'array_of_objects',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/array_of_objects_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderComplexModel(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $address = new \stdClass();
        $address->street = 'Elm Street';
        $address->number = 123;
        $address->city = 'Springwood';
        $address->state = 'Ohio';
        $customer = new \stdClass();
        $customer->name = 'Peter Parker';
        $customer->taxId = '123.456.789-00';
        $customer->phone = '(11) 98765-4321';
        $customer->address = $address;
        $customer->transactions = [
            (object) [
                'id' => '1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p',
                'status' => 'approved',
                'amount' => 123.45,
                'createdAt' => new \DateTimeImmutable('2025-01-02T12:01:02.000Z'),
            ],
            (object) [
                'id' => '2b3c4d5e-6f7g-8h9i-0j1k-2l3m4n5o6p7q',
                'status' => 'declined',
                'amount' => 67.89,
                'createdAt' => new \DateTimeImmutable('2025-01-03T12:01:02.000Z'),
            ],
            (object) [
                'id' => '3c4d5e6f-7g8h-9i0j-1k2l-3m4n5o6p7q8r',
                'status' => 'declined',
                'amount' => 67.89,
                'createdAt' => new \DateTimeImmutable('2025-01-04T12:01:02.000Z'),
            ],
            (object) [
                'id' => '4d5e6f7g-8h9i-0j1k-2l3m-4n5o6p7q8r9s',
                'status' => 'approved',
                'amount' => 167.89,
                'createdAt' => new \DateTimeImmutable('2025-01-05T12:01:02.000Z'),
            ],
        ];
        $model = new \stdClass();
        $model->title = 'Transactions';
        $model->totalAmount = 1234.56;
        $model->updatedAt = new \DateTimeImmutable('2025-01-01T12:01:02.000Z');
        $model->customer = $customer;
        $data = new \stdClass();
        $data->model = $model;
        $view = new View(
            viewPath: 'complex_view',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/complex_view_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderNestedObjectsInTable(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $order1 = new \stdClass();
        $order1->id = 'ORD-001';
        $order1->customer = (object) ['name' => 'Alice Smith', 'address' => (object) ['city' => 'Madrid']];
        $order2 = new \stdClass();
        $order2->id = 'ORD-002';
        $order2->customer = (object) ['name' => 'Bob Jones', 'address' => (object) ['city' => 'Barcelona']];
        $model = new \stdClass();
        $model->title = 'Orders';
        $model->orders = [$order1, $order2];
        $data = new \stdClass();
        $data->model = $model;
        $view = new View(
            viewPath: 'nested_table',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/nested_table_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderTwoLevelNestedLists(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $section1 = new \stdClass();
        $section1->title = 'Fruits';
        $section1->items = [
            (object) ['name' => 'Apple', 'value' => 1.20],
            (object) ['name' => 'Banana', 'value' => 0.80],
        ];
        $section2 = new \stdClass();
        $section2->title = 'Vegetables';
        $section2->items = [
            (object) ['name' => 'Carrot', 'value' => 0.50],
            (object) ['name' => 'Lettuce', 'value' => 1.00],
        ];
        $model = new \stdClass();
        $model->pageTitle = 'Catalog';
        $model->sections = [$section1, $section2];
        $data = new \stdClass();
        $data->model = $model;
        $view = new View(
            viewPath: 'two_level_nesting',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/two_level_nesting_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderWithBranchOptions(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $model = new BranchModel(
            name: 'Peter Parker',
            description: 'Friendly neighborhood Spider',
            isBooleanProperty: true
        );
        $data = new \stdClass();
        $data->model = $model;
        $data->isProperty = true;
        $data->hasItems = true;
        $data->items = ['option 1', 'option 2', 'option 3'];
        $data->arrayProperty = [
            'my-key' => (object) [
                'name' => 'My Value',
            ],
        ];

        $view = new View(
            viewPath: 'branch_view',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/branch_view_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderWithLayout(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $model = new \stdClass();
        $model->name = 'Peter Parker';
        $model->age = 25;
        $model->height = 1.75;
        $model->title = 'Layout Test';
        $data = new \stdClass();
        $data->model = $model;
        $data->pageTitle = 'Layout page title';
        $view = new View(
            viewPath: 'view_with_layout',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $expected = file_get_contents("{$this->basePath}/view_with_layout_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderThrowsRuntimeExceptionWhenTemplateCannotBeRead(): void
    {
        $root = vfsStream::setup('root');
        $dir = vfsStream::newDirectory('views')->at($root);
        vfsStream::newFile('index.html', 0o000)->at($dir);

        $settings = new HtmlViewEngineSettings(basePath: vfsStream::url('root'), viewPath: 'views');
        $viewEngine = new HtmlViewEngine(settings: $settings, contentReplacer: $this->createStub(ContentReplacer::class));

        $view = new View(viewPath: 'index', data: null, headers: [], statusCode: StatusCode::Ok);

        // file_get_contents emits E_WARNING on failure; suppress it so failOnWarning does not fire.
        set_error_handler(static fn (): bool => true);

        try {
            $viewEngine->render($view, new RequestContext());
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Could not read template', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    public function testRenderFailWhenLayoutDoesNotExist(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);
        $model = new \stdClass();
        $view = new View(
            viewPath: 'view_without_layout',
            data: $model,
            headers: [],
            statusCode: StatusCode::Ok
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Layout not found: fakelayout');

        $this->viewEngine->render($view, $this->getRequestContext());
    }

    public function testRenderWithDynamicI18nKeyComposedFromModel(): void
    {
        $this->fileManager
            ->method('readKeyValueJson')
            ->willReturn([
                'flash.success' => 'Operation completed',
            ])
        ;

        $model = new \stdClass();
        $model->status = 'success';
        $data = new \stdClass();
        $data->model = $model;

        $view = new View(
            viewPath: 'dynamic_i18n',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );

        $expected = file_get_contents("{$this->basePath}/dynamic_i18n_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    public function testRenderDynamicDayOfWeekI18nInLoop(): void
    {
        $this->fileManager
            ->method('readKeyValueJson')
            ->willReturn([
                'availabilities.dayOfWeek.1' => 'Monday',
                'availabilities.dayOfWeek.2' => 'Tuesday',
            ])
        ;

        $availability1 = new \stdClass();
        $availability1->dayOfWeekId = 1;
        $availability2 = new \stdClass();
        $availability2->dayOfWeekId = 2;

        $data = new \stdClass();
        $data->availabilities = [$availability1, $availability2];

        $view = new View(
            viewPath: 'dynamic_availabilities_i18n',
            data: $data,
            headers: [],
            statusCode: StatusCode::Ok
        );

        $expected = file_get_contents("{$this->basePath}/dynamic_availabilities_i18n_expected.html");

        $body = $this->viewEngine->render($view, $this->getRequestContext());

        $this->assertSame($expected, $body);
    }

    private function getRequestContext(): RequestContext
    {
        $requestContext = new RequestContext();
        $requestContext->set(RequestContextKeys::Language->value, 'en');
        $identity = $this->createStub(Identity::class);
        $identity->method('isAuthenticated')->willReturn(false);
        $identity->method('username')->willReturn('anonymous');
        $requestContext->setIdentity($identity);

        return $requestContext;
    }
}
