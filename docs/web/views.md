# Views & Templates

The framework uses a lightweight template engine. Templates are plain `.html` files — no PHP execution inside templates.

## Template language reference

| Syntax | Description |
|--------|-------------|
| `{{var}}` | HTML-escaped variable output. |
| `{{obj->prop}}` | Access object property (HTML-escaped). |
| `{{arr.key}}` | Access array key (HTML-escaped). |
| `{{{var}}}` | **Raw** (unescaped) HTML — use only for already-sanitized content. |
| `{{#if cond:}} ... {{#endif cond:}}` | Conditional block. `cond` is a model key. |
| `{{#for item in items:}} ... {{#endfor items:}}` | Loop over an array. |
| `{{#layout name:}}` | Wrap this template in a layout file. |
| `{{content}}` | Inside a layout: marks where the child template is injected. |
| `{{i18nKey}}` | Resolved from `{locale}.json` in `i18nBasePath`. |

### Variable output

```html
<!-- HTML-escaped (safe for all user-supplied values) -->
<p>Hello, {{user->name}}!</p>

<!-- Raw HTML (only for sanitized/trusted content) -->
<div>{{{article->htmlBody}}}</div>
```

### Conditionals

```html
{{#if isAdmin:}}
  <a href="/admin">Admin panel</a>
{{#endif isAdmin:}}
```

The condition key is resolved from the model/data passed to the view. A truthy value (non-empty string, non-zero number, `true`) shows the block.

### Loops

```html
<ul>
{{#for article in articles:}}
  <li>
    <a href="/articles/{{article->id}}">{{article->title}}</a>
  </li>
{{#endfor articles:}}
</ul>
```

### Layouts

`layout.html`:

```html
<!DOCTYPE html>
<html>
<head>
  <title>{{pageTitle}}</title>
  <link rel="stylesheet" href="/{{mainCssBundler}}">
</head>
<body>
  <nav><!-- shared nav --></nav>
  <main>
    {{content}}
  </main>
  <script src="/{{mainJsBundler}}"></script>
</body>
</html>
```

Child template `Views/Article/show.html`:

```html
{{#layout layout:}}

<article>
  <h1>{{article->title}}</h1>
  <p>{{article->body}}</p>
</article>
```

### Internationalization

```html
<button>{{save_button_label}}</button>
```

`assets/i18n/en.json`:

```json
{
  "save_button_label": "Save changes"
}
```

`assets/i18n/es.json`:

```json
{
  "save_button_label": "Guardar cambios"
}
```

The locale is detected automatically from the request and the correct translation file is loaded.

## Passing data to views

From a controller action:

```php
return $this->view(model: $article, data: [
    'pageTitle' => $article->title,
    'breadcrumbs' => [['Home', '/'], ['Articles', '/articles']],
]);
```

- **`model`** — available as `{{model->...}}` in the template.
- **`data`** — merged into the top-level template context; keys are available directly as `{{pageTitle}}`.

## Security

All `{{var}}` output is HTML-escaped via `htmlspecialchars()`. Only `{{{var}}}` skips escaping. Treat `{{{...}}}` as you would `echo $htmlString` — only use it for content you control or have already sanitized.
