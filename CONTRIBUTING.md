# Contributing

Thank you for considering contributing to `aseguragonzalez/php-mvc`.

## Requirements

- PHP 8.3 or 8.4
- Docker (recommended) or a local PHP environment with Composer

## Getting started

```bash
git clone https://github.com/aseguragonzalez/php-mvc.git
cd php-mvc
make install
```

## Development workflow

All commands can be run via `make` (they delegate to Docker internally):

| Command | Description |
|---|---|
| `make test` | Run the test suite |
| `make cs` | Check code style (dry-run) |
| `make cs-fix` | Auto-fix code style |
| `make stan` | Run static analysis (PHPStan level 8) |
| `make check` | Run cs + stan + test |

## Submitting changes

1. Fork the repository and create a branch from `main`.
2. Add or update tests for any behaviour change.
3. Run `make check` and ensure it passes cleanly.
4. Open a pull request with a clear description of the change and its motivation.

## Code style

This project follows the rules defined in `.php-cs-fixer.dist.php`. Run `make cs-fix` before committing.

## Reporting security issues

Please do **not** open a public GitHub issue for security vulnerabilities. See [SECURITY.md](SECURITY.md) for the responsible disclosure process.
