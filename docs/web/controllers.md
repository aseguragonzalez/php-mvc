# Controllers

Controllers are regular PHP classes that handle an HTTP action and return an `ActionResponse`. They are resolved from the PSR-11 container, so constructor injection works out of the box.

## Defining a controller

```php
<?php

declare(strict_types=1);

namespace App\MyApp\Controllers;

use PhpMvc\Controllers\Controller;
use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Actions\Responses\View;
use PhpMvc\Actions\Responses\RedirectTo;
use PhpMvc\Actions\Responses\LocalRedirectTo;

final class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepository $articles,
    ) {}

    public function index(): ActionResponse
    {
        $articles = $this->articles->findAll();
        return $this->view(model: (object) ['articles' => $articles]);
    }

    public function show(int $id): ActionResponse
    {
        $article = $this->articles->findById($id);
        return $this->view(model: $article);
    }

    public function store(ArticleRequest $request): ActionResponse
    {
        $this->articles->save($request);
        return $this->redirectToAction('index');
    }
}
```

## `ActionResponse` subtypes

### `View`

Renders an HTML template. The view path is resolved relative to the configured views directory.

```php
// Renders Views/Article/index.html (convention: ControllerName/action)
return $this->view();

// Explicit path (no .html extension)
return $this->view(name: 'Article/list');

// Pass a model
return $this->view(model: $article);
```

The `Controller::view()` helper infers the view path from the calling class and method name when not given explicitly.

### `RedirectTo`

Redirects to an absolute URL (http/https only):

```php
return RedirectTo::create(url: 'https://example.com/external');
return RedirectTo::create(url: 'https://example.com/new-path', args: ['ref' => 'old']);
```

The status code is always `302 Found`.

### `LocalRedirectTo`

Redirects to another controller/action within the same app. The URL is resolved automatically — you never hardcode paths.

Use the `redirectToAction()` helper from `Controller`:

```php
// Same controller, no args
return $this->redirectToAction('index');

// Different controller, with args
return $this->redirectToAction(
    action: 'show',
    controller: ArticleController::class,
    args: (object) ['id' => $newId],
);
```

Or call `LocalRedirectTo::create()` directly when outside a controller:

```php
return LocalRedirectTo::create(
    action: 'show',
    controller: ArticleController::class,
    args: (object) ['id' => $newId],
);
```

## Wiring to routes

Controllers are referenced by their fully-qualified class name in `Route::create()`. There is no annotation magic — the link between a route and a controller is explicit:

```php
Route::create(
    RouteMethod::Get,
    Path::create('/articles/{int:id}'),
    ArticleController::class, // FQCN
    'show',                   // method name as string
);
```

## Action parameter resolution

Action arguments are resolved automatically from the request:

1. **Route path parameters** — type-coerced from the URL.
2. **Query string** — scalar keys mapped by parameter name.
3. **Parsed body** — for `POST`/`PUT`/`PATCH`/`DELETE` requests.
4. **`ServerRequestInterface`** — injected automatically if the action declares it.
5. **DTO objects** — non-scalar parameters are constructed from request input.

See [Request Binding](request-binding.md) for the full binding rules.
