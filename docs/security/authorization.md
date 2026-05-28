# Authorization

The `Authorization` middleware enforces route-level access control. It reads security metadata from the matched `Route` and redirects or blocks requests that don't meet the requirements.

## Enable

```php
$app->useAuthentication(); // must run before Authorization
$app->useAuthorization();
```

Both must be enabled together — `Authorization` reads the identity stored by `Authentication`.

## Route security metadata

Routes declare their access requirements at definition time:

```php
Route::create(
    RouteMethod::Get,
    Path::create('/dashboard'),
    DashboardController::class,
    'index',
    authRequired: true,        // requires a logged-in user
    roles: ['user', 'admin'],  // user must have at least one of these roles
);

Route::create(
    RouteMethod::Delete,
    Path::create('/admin/users/{int:id}'),
    AdminController::class,
    'destroy',
    authRequired: true,
    roles: ['admin'],          // admin only
);

Route::create(
    RouteMethod::Get,
    Path::create('/'),
    HomeController::class,
    'index',
    // authRequired defaults to false — public route
);
```

## Authorization rules

| Condition | Result |
|-----------|--------|
| `authRequired: false` | Route is public; middleware passes through. |
| `authRequired: true`, no identity in context | Redirect to `AuthSettings::signInPath` + clear auth cookie. |
| `authRequired: true`, identity present, `roles: []` | Any authenticated user is allowed. |
| `authRequired: true`, identity present, role match | Allowed. |
| `authRequired: true`, identity present, no role match | `403 Forbidden`. |

## Configuring the sign-in redirect

Register `AuthSettings` in your composition root:

```php
use PhpMvc\AuthSettings;

$container->set(AuthSettings::class, new AuthSettings(
    signInPath: '/account/sign-in',
    cookieName: 'auth', // default; override if needed
));
```

When an unauthenticated request hits a protected route, the middleware redirects to `signInPath` and clears the auth cookie.

## Role model

An authenticated user is allowed if their `Identity::getRoles()` intersects the route's `roles` array. An empty `roles` array means "any authenticated user."
