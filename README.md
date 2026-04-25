# alfonsosegura/mvc-framework

[![CI](https://github.com/aseguragonzalez/mvc-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/aseguragonzalez/mvc-framework/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/alfonsosegura/mvc-framework.svg)](https://packagist.org/packages/alfonsosegura/mvc-framework)
[![PHP Version](https://img.shields.io/packagist/php-v/alfonsosegura/mvc-framework.svg)](https://packagist.org/packages/alfonsosegura/mvc-framework)
[![License](https://img.shields.io/github/license/aseguragonzalez/mvc-framework.svg)](LICENSE)

Lightweight PHP 8.3+ MVC framework built around DDD and Hexagonal Architecture. Provides a PSR-7/PSR-15 web layer, a dependency-injection container, a CLI scaffolding tool, and optional modules for database migrations, background tasks, and authentication.

## Requirements

- PHP 8.3 or 8.4
- Composer

## Installation

```bash
composer require alfonsosegura/mvc-framework
```

## Quickstart

### 1. Scaffold a new app

```bash
vendor/bin/mvc create-app ./src/Ports/MyApp --name=MyApp --namespace=App\\Ports\\MyApp
```

This generates the folder structure, `mvc.config.json`, and a bootstrap file.

### 2. Define a controller

```php
use AlfonsoSG\Mvc\Controllers\Controller;
use AlfonsoSG\Mvc\Actions\Responses\ActionResponse;

class HomeController extends Controller
{
    public function index(): ActionResponse
    {
        return $this->view();               // renders Home/index
    }

    public function show(int $id): ActionResponse
    {
        $model = /* … */;
        return $this->view(model: $model);  // renders Home/show
    }
}
```

### 3. Register routes

```php
use AlfonsoSG\Mvc\Routes\Router;

$router = new Router();
$router->get('/', HomeController::class, 'index');
$router->get('/items/{id}', HomeController::class, 'show');
```

### 4. Handle requests

```php
use AlfonsoSG\Mvc\Requests\RequestHandler;

$handler = new RequestHandler($container, $router);
$response = $handler->handle($serverRequest);
```

## CLI

The `mvc` binary provides scaffolding and asset bundling commands:

| Command | Description |
|---|---|
| `mvc create-app` | Scaffold a new MVC app |
| `mvc migrations:enable` | Enable the migrations module |
| `mvc migrations:create` | Create a new timestamped migration |
| `mvc migrations:run` | Run all pending migrations |
| `mvc migrations:test` | Test a single migration (apply + rollback) |
| `mvc auth:enable` | Enable the authentication module |
| `mvc bg-tasks:enable` | Enable the background tasks module |
| `mvc watch-assets` | Watch and rebuild JS/CSS bundles (dev) |
| `mvc create-bundle` | Build minified JS/CSS bundles (production) |

## Modules

### Security

Sign-up, sign-in, password reset, and session refresh use cases are provided as application services under `AlfonsoSG\Mvc\Security\Application\`. Wire them through the DI container and expose them via controllers.

### Migrations

SQL migrations are stored as timestamped folders. See [`src/Apps/HowToMigrations.md`](src/Apps/HowToMigrations.md) for the full workflow.

### Background tasks

Register tasks and process them via `AlfonsoSG\Mvc\BackgroundTasks\Application\`. See [`src/Apps/HowToBackgroundTasks.md`](src/Apps/HowToBackgroundTasks.md).

### Asset bundling

Merge and optionally minify JS/CSS sources via the CLI. See [`src/Web/HowToAssets.md`](src/Web/HowToAssets.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) for the responsible disclosure process.

## License

MIT. See [LICENSE](LICENSE).
