# Quickstart

This guide takes you from zero to a running MVC app in five steps.

## 1. Scaffold the app

```bash
vendor/bin/mvc create-app ./src/MyApp --name=MyApp --namespace=App\\MyApp
```

This generates:

```
src/MyApp/
├── Controllers/
├── Views/
├── assets/
│   ├── i18n/
│   │   └── en.json
│   ├── scripts/
│   │   └── main.js
│   └── styles/
│       └── main.css
├── MyAppApp.php
├── MyAppBootstrap.php
└── mvc.config.json
```

- **`MyAppApp.php`** — extends `MvcWebApp`; wire routes here.
- **`MyAppBootstrap.php`** — composition root; register settings, PDO, and services on the container.
- **`mvc.config.json`** — feature flags and paths (see [Configuration Reference](configuration.md)).

## 2. Define a controller

```php
<?php

declare(strict_types=1);

namespace App\MyApp\Controllers;

use PhpMvc\Controllers\Controller;
use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Actions\Responses\View;

final class HomeController extends Controller
{
    public function index(): ActionResponse
    {
        return $this->view();
    }

    public function show(int $id): ActionResponse
    {
        $model = (object) ['id' => $id, 'title' => "Item {$id}"];
        return $this->view(model: $model);
    }
}
```

## 3. Register routes

Open `MyAppApp.php` and register routes in the `router()` method:

```php
<?php

declare(strict_types=1);

namespace App\MyApp;

use PhpMvc\MvcWebApp;
use PhpMvc\Routes\Router;
use PhpMvc\Routes\Route;
use PhpMvc\Routes\RouteMethod;
use PhpMvc\Routes\Path;
use App\MyApp\Controllers\HomeController;

final class MyAppApp extends MvcWebApp
{
    protected function router(): Router
    {
        $router = new Router();

        $router->register(Route::create(
            RouteMethod::Get,
            Path::create('/'),
            HomeController::class,
            'index',
        ));

        $router->register(Route::create(
            RouteMethod::Get,
            Path::create('/items/{int:id}'),
            HomeController::class,
            'show',
        ));

        return $router;
    }
}
```

## 4. Create a view

Create `src/MyApp/Views/Home/index.html`:

```html
<!DOCTYPE html>
<html>
<head><title>My App</title></head>
<body>
  <h1>Hello from PHP MVC</h1>
  <a href="/items/1">View item 1</a>
</body>
</html>
```

And `src/MyApp/Views/Home/show.html`:

```html
<!DOCTYPE html>
<html>
<head><title>Item {{model->id}}</title></head>
<body>
  <h1>{{model->title}}</h1>
</body>
</html>
```

## 5. Create the entrypoint

Create `public/index.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use App\MyApp\MyAppApp;
use App\MyApp\MyAppBootstrap;

$container = new Container();
MyAppBootstrap::register($container, __DIR__ . '/../');
$app = new MyAppApp(container: $container, basePath: __DIR__ . '/../');

exit($app->run());
```

## 6. Run the built-in server

```bash
php -S localhost:8080 -t public
```

Open `http://localhost:8080` — you should see **Hello from PHP MVC**.

## Next steps

- [Configuration Reference](configuration.md) — learn every `mvc.config.json` key.
- [Routing](../web/routing.md) — typed path parameters, auth-protected routes.
- [Views & Templates](../web/views.md) — template language reference.
- [CLI Reference](../cli/reference.md) — all `mvc` commands.
