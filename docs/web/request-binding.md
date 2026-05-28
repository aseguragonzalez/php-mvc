# Request Binding

Action method parameters are resolved and type-coerced automatically from the incoming request. You rarely need to touch the raw request object.

## Resolution order

For each parameter of a controller action, the framework checks sources in this order:

1. **Route path parameters** — extracted and typed from the URL (e.g. `{int:id}` → `int $id`).
2. **Query string** — scalar parameters matched by name from `?key=value`.
3. **Parsed request body** — for `POST`, `PUT`, `PATCH`, `DELETE` requests.
4. **PSR-7 request** — if the parameter type is `ServerRequestInterface`, the request object is injected directly.
5. **DTO / object** — any non-scalar class is treated as a request object and constructed from the flat input.

## Scalar normalization

All scalar values are automatically normalized:

| PHP type | Behavior |
|----------|----------|
| `string` | Trimmed. |
| `int` | Digits-only strings cast to `int`; non-numeric input → `null`. |
| `float` | Numeric strings cast to `float`; non-numeric → `null`. |
| `bool` | `"1"`, `"true"`, `"on"`, `"yes"` → `true`; `"0"`, `"false"`, `"off"`, `"no"` → `false`. |

Returning `null` for invalid numeric input lets your action or DTO detect missing/malformed data explicitly:

```php
public function show(?int $id): ActionResponse
{
    if ($id === null) {
        return $this->view(name: 'errors/not-found', statusCode: StatusCode::NotFound);
    }
    // ...
}
```

## DTO binding

Non-scalar parameters are constructed using named constructor arguments:

```php
final class CreateArticleRequest
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly int $categoryId,
    ) {}
}

// Controller action:
public function store(CreateArticleRequest $request): ActionResponse { ... }
```

The input keys `title`, `body`, and `categoryId` (from the request body) are mapped to constructor parameters by name.

### Dotted keys for nested objects

```php
final class AddressRequest
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

final class ShippingRequest
{
    public function __construct(
        public readonly AddressRequest $address,
    ) {}
}
```

HTML form fields:

```html
<input name="address.street" value="123 Main St">
<input name="address.city" value="Springfield">
```

### Array binding

```php
final class BatchRequest
{
    /**
     * @param array<TagRequest> $tags
     */
    public function __construct(
        public readonly array $tags,
    ) {}
}
```

HTML:

```html
<input name="tags[0][name]" value="php">
<input name="tags[1][name]" value="mvc">
```

The `@param array<Type>` docblock is required to infer the array element type.

## Injecting the PSR-7 request

For cases where you need the raw request (e.g. reading headers):

```php
use Psr\Http\Message\ServerRequestInterface;

public function upload(ServerRequestInterface $request): ActionResponse
{
    $contentType = $request->getHeaderLine('Content-Type');
    // ...
}
```

The framework detects the `ServerRequestInterface` type hint and injects the current request object directly.
