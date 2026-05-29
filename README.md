# PHP MVC

[![Packagist Version](https://img.shields.io/packagist/v/aseguragonzalez/php-mvc)](https://packagist.org/packages/aseguragonzalez/php-mvc)
[![PHP](https://img.shields.io/packagist/php-v/aseguragonzalez/php-mvc)](https://packagist.org/packages/aseguragonzalez/php-mvc)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![CI](https://github.com/aseguragonzalez/php-mvc/actions/workflows/ci.yml/badge.svg)](https://github.com/aseguragonzalez/php-mvc/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/aseguragonzalez/php-mvc/branch/main/graph/badge.svg)](https://codecov.io/gh/aseguragonzalez/php-mvc)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-blue)](https://phpstan.org/)
[![PSR-15](https://img.shields.io/badge/PSR--15-compatible-brightgreen)](https://www.php-fig.org/psr/psr-15/)

Lightweight PHP 8.4+ MVC framework for building simple web applications. Routing, controllers, middleware, views, and a small set of opt-in modules (migrations, authentication, background tasks) — with only PSR standard interfaces as runtime dependencies.

## Requirements

- PHP 8.4 or later
- Composer 2.x

## Getting started

### Install

```bash
composer require aseguragonzalez/php-mvc
```

### Create an app

```bash
vendor/bin/mvc create-app ./src/MyApp --name=MyApp --namespace=App\\MyApp
```

This generates the folder structure, a bootstrap file, and an `mvc.config.json` configuration file.

### Enable optional modules

Each module is opt-in and can be activated through the CLI:

```bash
vendor/bin/mvc migrations:enable   # SQL migrations via timestamped scripts
vendor/bin/mvc auth:enable         # Authentication and authorization
vendor/bin/mvc bg-tasks:enable     # Background task processing
```

Once enabled, each module exposes additional CLI commands (e.g. `migrations:create`, `migrations:run`). See the [CLI reference](https://aseguragonzalez.github.io/php-mvc/cli/reference/) for the full list.

## Documentation

Full documentation is available at [aseguragonzalez.github.io/php-mvc](https://aseguragonzalez.github.io/php-mvc/).

## Development

### Dev container

All tooling (PHP, Composer, PHPStan, PHP-CS-Fixer, MkDocs) runs inside the dev container. Start it once from the project root:

```bash
devcontainer up --workspace-folder .
```

Then run any make target with:

```bash
devcontainer exec --workspace-folder . make <target>
```

**Debugging:** The Xdebug port is **9003**. Configure your IDE or Xdebug client to connect to that port.

### Make targets

All targets must be run inside the dev container — the required tools are not available on the host.

| Command | Description |
|---|---|
| `make install` | Install Composer dependencies |
| `make test` | Run the test suite |
| `make cs` | Check code style (dry-run) |
| `make cs-fix` | Auto-fix code style |
| `make stan` | Run static analysis (PHPStan max) |
| `make check` | Run `cs + stan + test` |
| `make all` | Run `install + cs-fix + check` |
| `make docs-serve` | Serve the documentation site on port 8001 |

### Documentation site

The documentation site uses [MkDocs Material](https://squidfunk.github.io/mkdocs-material/). Dependencies are installed automatically when the dev container is created.

```bash
devcontainer exec --workspace-folder . make docs-serve
```

Then open **http://localhost:8001/php-mvc/** in your browser. The dev container forwards port 8001 automatically; if you use VS Code with the Dev Containers extension the browser opens on its own.

## Built with

- [PHPUnit](https://phpunit.de/) ^12.5 — test suite
- [PHPStan](https://phpstan.org/) ^2.1 — static analysis at max level
- [PHP-CS-Fixer](https://cs.symfony.com/) ^3.95 — code style
- [FakerPHP](https://fakerphp.org/) ^1.24 — test data generation
- [vfsStream](https://github.com/bovigo/vfsStream) ^1.6 — virtual filesystem for tests

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](.github/CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](.github/CODE_OF_CONDUCT.md) before opening a pull request.

## Security

See [SECURITY.md](.github/SECURITY.md) for the responsible disclosure process.

## License

[MIT License](LICENSE). Copyright (c) 2026 Alfonso Segura.
