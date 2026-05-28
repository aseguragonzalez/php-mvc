# CSRF Protection

The framework provides built-in Cross-Site Request Forgery protection via the `CsrfProtection` middleware.

## Enable

```php
$app->useCsrfProtection();
```

Call this before `run()`.

## How it works

### Safe methods (GET, HEAD, OPTIONS)

A CSRF token is generated and made available for rendering in views.

### Unsafe methods (POST, PUT, PATCH, DELETE)

The middleware validates the CSRF token from **one** of two sources:

1. **Form body field** — `_csrf`
2. **HTTP header** — `X-CSRF-Token`

If neither is present or the value doesn't match the session token, the middleware returns `403 Forbidden` with a short error message. The inner chain (controllers) is never reached.

## Reading the token in controllers

```php
use PhpMvc\Middlewares\CsrfProtection;
use PhpMvc\Requests\RequestContext;

final class FormController extends Controller
{
    public function __construct(
        private readonly RequestContext $context,
    ) {}

    public function create(): ActionResponse
    {
        $token = CsrfProtection::getTokenFromContext($this->context);
        return $this->view(model: ['csrfToken' => $token]);
    }
}
```

## Adding the token to HTML forms

```html
<form method="post" action="/articles">
    <input type="hidden" name="_csrf" value="{{model->csrfToken}}">

    <label>Title</label>
    <input type="text" name="title">

    <button type="submit">Create</button>
</form>
```

## AJAX requests

For JavaScript-driven requests, read the token from a `<meta>` tag and send it as a header:

```html
<meta name="csrf-token" content="{{csrfToken}}">
```

```js
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/articles', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
    },
    body: JSON.stringify({ title: 'New article' }),
});
```

## Security notes

- The token is bound to the user session. Rotating the session (e.g. on sign-in) invalidates the old token.
- The `_csrf` body field takes precedence over the `X-CSRF-Token` header when both are present.
- Safe methods do not consume the token; the same token is valid across multiple GET requests within a session.
