# CLAUDE.md

## Project

`aseguragonzalez/php-mvc` — Lightweight PHP 8.4+ MVC framework. Packagist library, no runtime dependencies beyond PSR interfaces. Binary: `bin/mvc` (also installed as `vendor/bin/mvc`).

PHP namespace: `PhpMvc\` (all source under `src/`).

## Commands

```bash
make install      # composer install
make test         # phpunit
make cs           # php-cs-fixer --dry-run --diff
make cs-fix       # php-cs-fixer fix
make stan         # phpstan analyse
make check        # cs + stan + test (full suite)
make all          # install + cs-fix + check (flujo completo)
make docs-serve   # MkDocs dev server at http://localhost:8001
```

## Source layout

```
src/
├── Web/           # Router, controllers, middleware, views, request/response
├── Apps/          # Optional modules: Migrations, BackgroundTasks
├── Functional/    # Security (auth/authz), file utilities
├── Cli/           # mvc binary and Commands
└── DevTools/      # Development-only helpers
```

Tests mirror the same tree under `tests/`.

## Key conventions

- **PHP 8.4**, strict types on every file (`declare(strict_types=1)`).
- **PHPStan at max level** — no baseline, no suppression comments. All new code must pass.
- **PHP-CS-Fixer** enforces the style — run `make cs-fix` before committing.
- **No runtime dependencies** beyond PSR contracts (`psr/container`, `psr/http-*`, `psr/log`). Do not add Composer `require` entries without discussion.
- Namespace root `PhpMvc\` maps to multiple `src/` subdirectories via PSR-4 (see `composer.json`).

## Documentation site

MkDocs Material site in `docs/` — nav defined in `mkdocs.yml`. Serve locally with `make docs-serve` (requires Python venv at `~/.venv` with `mkdocs-material` and `mike`). Published to GitHub Pages via `mike`.

## Testing

```bash
make test                      # full suite (723 tests)
vendor/bin/phpunit --filter X  # single test or class
```

All 723 tests are unit tests — no real database required. Infrastructure tests (SQL repositories, schema executors) mock `PDO` via `createMock(\PDO::class)`.

## Quality checks before committing

```bash
make check   # cs + stan + test — must be green
```

## Validation environment

**All validation runs inside the devcontainer.** PHP and Composer are not available on the host. Use:

```bash
docker exec <container> bash -c "cd /workspaces/php-mvc && composer stan"
docker exec <container> bash -c "cd /workspaces/php-mvc && composer test"
docker exec <container> bash -c "cd /workspaces/php-mvc && composer check"
```

Never report a quality check as passing without running it in the devcontainer first.
