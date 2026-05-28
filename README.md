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
