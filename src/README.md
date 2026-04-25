# Framework MVC

The `Framework\Mvc` package provides a small, opinionated MVC stack on top of the SeedWork core:

- **Routing** (`Routes\Router`, `Routes\Route`, `Routes\RouteMethod`, `Routes\Path`)
- **HTTP pipeline** (`MvcWebApp`, middlewares, `Requests\RequestHandler`)
- **Actions / controllers** (`Actions\Responses\*`, `Controllers\*`)
- **Views** (`Views\HtmlViewEngine` and the view pipeline)
- **Security** (authentication, authorization, CSRF, identity management)

It is designed for:

- **Security by default**: normalized inputs, auto-escaped views, CSRF protection, auth/role-aware routing.
- **Predictable behavior**: strong typing for action parameters and DTOs, explicit error handling.
- **Simple integration**: everything is wired through `MvcWebApp` and the DI container.

---

## Request lifecycle (public API overview)

High-level flow for an HTTP request:

1. The composition root registers services on the DI container; then `MvcWebApp::run()` wires MVC-only services and the middleware chain.
2. `MvcWebApp::handleRequest()` builds a PSR-7 `ServerRequestInterface` from globals.
3. Middlewares run in order (outermost first):
   - `ErrorHandling` → `Localization` → *optional* `CsrfProtection`
   - *optional* `Authentication` → *optional* `Authorization`
4. The last middleware delegates to `Requests\RequestHandler`, which:
   - Resolves a `Route` via `Router::get()`
   - Builds action parameters from route args, query params, and body
   - Invokes the controller action
   - Converts the `ActionResponse` into a PSR-7 `ResponseInterface`
5. `MvcWebApp` sends status, headers and body to PHP’s output functions.

---

## Bootstrapping an MVC app

Create your application by extending `MvcWebApp` and wiring routes and middlewares through the container:

```php
final class WebApp extends MvcWebApp
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
            RouteMethod::Post,
            Path::create('/account/{uuid:id}/update'),
            AccountController::class,
            'update',
            authRequired: true,
            roles: ['user', 'admin'],
        ));

        return $router;
    }
}
```

In your composition root (e.g. `public/index.php`) register settings, logging, and dependencies on the container, then construct and run the app:

```php
$container = new \DI\Container();
MyAppBootstrap::register($container, __DIR__ . '/../');
$app = new WebApp($container, basePath: __DIR__ . '/../');

$app->useAuthentication();
$app->useAuthorization();
$app->useCsrfProtection();

exit($app->run());
```

`MvcWebApp::run()` does not populate the container; anything your middlewares and controllers need must already be registered before `run()`.

Public knobs on `MvcWebApp`:

- `addMiddleware(string $middleware)` – register custom middlewares (run inside the fixed chain).
- `useAuthentication()` – require authentication middleware.
- `useAuthorization()` – require authorization middleware.
- `useCsrfProtection()` – validate CSRF tokens on state-changing requests.

---

## Routing API

The routing API lives under `Framework\Mvc\Routes` and is intentionally small:

- `Router` – collection of `Route` objects.
- `Route::create(RouteMethod $method, Path $path, string $controller, string $action, bool $authRequired = false, array $roles = [])`
- `RouteMethod` – HTTP method enum-like (`Get`, `Post`, etc.).
- `Path::create(string $pattern)` – typed path; supports typed parameters:
  - `{id}` → string
  - `{int:id}` → int
  - `{float:amount}` → float
  - `{uuid:id}` → UUID v4 string

Finding routes:

- `Router::get(RouteMethod $method, string $path): Route` – find matching route or throw `RouteDoesNotFoundException`.
- `Router::getFromControllerAndAction(string $controller, string $action): ?Route` – used for local redirects.

Security metadata:

- `authRequired` – when true, `Authorization` will enforce authentication.
- `roles` – list of roles; route allows any identity whose `getRoles()` intersects this list.

---

## Controllers and actions

Controllers are regular PHP classes (recommended in `Framework\Mvc\Controllers`) that:

- Are referenced from routes via their FQCN and method name.
- Return an `Actions\Responses\ActionResponse` subtype:
  - `View` – render an HTML view.
  - `RedirectTo` – redirect to external or computed URL.
  - `LocalRedirectTo` – redirect to another controller/action using `Router::getFromControllerAndAction`.

Example:

```php
use Framework\Mvc\Actions\Responses\View;
use Framework\Mvc\Actions\Responses\LocalRedirectTo;

final class AccountController
{
    public function edit(int $id): View
    {
        $account = /* load account by $id */;

        return new View(
            viewPath: 'Account/edit',
            data: ['model' => $account],
        );
    }

    public function update(int $id, AccountRequest $request): LocalRedirectTo
    {
        /* update account */

        return new LocalRedirectTo(
            controller: self::class,
            action: 'edit',
            args: (object) ['id' => $id],
        );
    }
}
```

### Action parameters and DTOs

`Requests\RequestHandler` and `Actions\ActionParameterBuilder` build action parameters from:

1. Route path parameters (from `Path` placeholders).
2. Query string parameters.
3. Parsed body (for `POST` and other state-changing methods).

Binding rules:

- Scalar-typed parameters (`int`, `float`, `string`) are normalized and coerced via `InputNormalizer`.
- A parameter of type `ServerRequestInterface` receives the PSR-7 request object.
- Non-scalar, non-built-in parameters are treated as **request/DTO objects** and are built using `ActionParameterBuilder`:
  - Constructor parameters are filled from flat keys like `id`, `name`.
  - Embedded objects use dotted keys: `address.street`, `address.city`.
  - Arrays use `name[0]`, `name[1]` and docblock `@param array<Type> $name` to infer element type.

This allows you to keep actions strongly typed and free from manual array plumbing.

---

## Views and templates

The views subsystem is documented in detail in `Views/README.md`. Key points for public usage:

- Configure views with `HtmlViewEngineSettings` and `LanguageSettings` in the container; `MvcWebApp` does this in `configureMvc()`.
- Return `View` responses from controller actions, passing:
  - `viewPath` – path relative to the configured views directory, without `.html`.
  - `data` – array or object; merged with request context.
- Use the template language:
  - Interpolation: `{{path}}` (now **HTML-escaped by default**).
  - Raw HTML (trusted only): `{{{path}}}`.
  - Loops: `{{#for item in items:}} ... {{#endfor items:}}`.
  - Conditionals: `{{#if expression:}} ... {{#endif expression:}}`.
  - Layouts: `{{#layout layoutName:}}` with `{{content}}` inside the layout file.
  - I18n keys: `{{key}}` resolved by `I18nReplacer` from `{locale}.json`.

---

## Security features and best practices

### Input normalization

`Requests\InputNormalizer` is used by both `RequestHandler` and `ActionParameterBuilder` to:

- Trim and normalize scalar values.
- Safely coerce to `int` / `float` / `string` / `bool`.
- Return `null` for invalid numeric input so actions/DTOs can explicitly handle missing or malformed data.

**Recommendation:** always give your action parameters and DTOs explicit types so invalid input is visible and testable.

### Auto-escaped views

- All `{{path}}` placeholders are HTML-escaped.
- Only `{{{path}}}` renders raw HTML; use this for already-sanitized content and keep its usage rare and reviewed.

### Authentication and authorization

Enable with:

- `MvcWebApp::useAuthentication()` – runs `Middlewares\Authentication`:
  - Reads auth token from cookie.
  - Loads identity via `Security\IdentityManager`.
  - Stores identity and token in `RequestContext`.
- `MvcWebApp::useAuthorization()` – runs `Middlewares\Authorization`:
  - Looks up the current `Route`.
  - Enforces `authRequired` and `roles` metadata.
  - Redirects unauthenticated users to `AuthSettings::signInPath` and clears the auth cookie.

### CSRF protection

Enable with:

- `MvcWebApp::useCsrfProtection()` – injects `Middlewares\CsrfProtection` into the chain.

Behavior:

- For safe methods (`GET`, `HEAD`, etc.) it only ensures a CSRF token exists and stores it in `RequestContext`.
- For protected methods (`POST`, `PUT`, `PATCH`, `DELETE`) it validates the token from either:
  - Body field `_csrf`, or
  - Header `X-CSRF-Token`.
- On failure, responds with `403 Forbidden` and a short error message.

Usage in forms:

```php
/** @var RequestContext $context */
$token = CsrfProtection::getTokenFromContext($context);

// In the template model
$data['csrfToken'] = $token;
```

```html
<form method="post" action="/account/{{model->id}}/update">
    <input type="hidden" name="_csrf" value="{{csrfToken}}">
    <!-- other fields -->
</form>
```

---

## MVC CLI

Use the MVC scaffolding tool to generate a new app structure:

```bash
mvc create-app <path> --name=<AppName> --namespace=<Namespace>
```

How-to details (including `mvc.config.json` defaults): see [How to Create a New MVC App (CLI)](./Cli/HowToCreateApp.md).

Database migrations (enable/disable, `mvc.config.json`, and `migrations:create` / `migrations:run` / `migrations:test`): see [How to use MVC database migrations](./Apps/HowToMigrations.md).

Authentication and authorization (`mvc auth:enable` / `mvc auth:disable`, `authenticationEnabled` in `mvc.config.json`, default SQL migrations): see [How to enable MVC authentication and authorization (CLI)](./Functional/HowToAuthentication.md).

CSS and JavaScript bundling (`mvc watch-assets`, `mvc create-bundle`, `assetRoutes` in `mvc.config.json`): see [How to bundle CSS and JavaScript (MVC CLI)](./Web/HowToAssets.md).

---

## Performance characteristics

The framework includes a few built-in optimizations:

- `Route` caches its compiled regex for path matching.
- `Router` indexes routes by HTTP method so `get()` scans only a subset of routes.
- `HtmlViewEngine` caches template contents in-memory per view path.
- `I18nReplacer` caches parsed JSON translation files per language.

These are transparent to consumers; no configuration is required.

---

## Where to look next

- `src/Framework/Mvc/Web/MvcWebApp.php` – application bootstrap and middleware wiring.
- `src/Framework/Mvc/Web/Requests/RequestHandler.php` – routing + action invocation contract.
- `src/Framework/Mvc/Web/Views/README.md` – full template language reference.
- `src/Framework/Mvc/Functional/Security/*` – authentication, identity and password flows; challenge delivery is the `ChallengeNotificator` port (implement and register in your app).

Use this README as the entry point when building or reviewing apps on top of the MVC package.
