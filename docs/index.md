# PHP MVC

[![CI](https://github.com/aseguragonzalez/php-mvc/actions/workflows/ci.yml/badge.svg)](https://github.com/aseguragonzalez/php-mvc/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/aseguragonzalez/php-mvc.svg)](https://packagist.org/packages/aseguragonzalez/php-mvc)
[![PHP Version](https://img.shields.io/packagist/php-v/aseguragonzalez/php-mvc.svg)](https://packagist.org/packages/aseguragonzalez/php-mvc)
[![License](https://img.shields.io/github/license/aseguragonzalez/php-mvc.svg)](https://github.com/aseguragonzalez/php-mvc/blob/main/LICENSE)

Lightweight PHP 8.4+ MVC framework. Provides a PSR-7/PSR-15 web layer, a CLI scaffolding tool, and optional modules for database migrations, background tasks, and authentication.

## Features

- **PSR-7 / PSR-15** — bring your own HTTP foundation; the framework adapts to your stack.
- **Typed routing** — path parameters coerced to `int`, `float`, `string`, or validated UUID.
- **Auto-escaped views** — `{{var}}` is HTML-escaped by default; raw HTML requires `{{{var}}}`.
- **CSRF protection** — token validated from form body or `X-CSRF-Token` header.
- **Cookie-based auth** — sign-up, sign-in, password reset, and session refresh use cases included.
- **Database migrations** — timestamped SQL folders with forward/rollback scripts and schema comparison.
- **Background tasks** — SQL-backed task queue with cron or in-process worker modes.
- **Asset bundling** — merge and minify JS/CSS with a single CLI command.
- **PHPStan max** — strictly typed throughout.

## Installation

```bash
composer require aseguragonzalez/php-mvc
```

Requires **PHP 8.4** and **Composer**.

## Where to start

- [Installation](getting-started/installation.md) — requirements, install, verify.
- [Quickstart](getting-started/quickstart.md) — scaffold your first app in minutes.
- [Architecture](core-concepts/architecture.md) — understand how the modules fit together.
- [CLI Reference](cli/reference.md) — every `mvc` command in one place.

## License

[MIT License](https://github.com/aseguragonzalez/php-mvc/blob/main/LICENSE).
Copyright (c) 2026 Alfonso Segura.
