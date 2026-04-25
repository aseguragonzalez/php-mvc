# How to use MVC database migrations

Migrations live under a **migration module** directory next to your app’s `index.php`. The module contains `index.php` (CLI entry) and a `migrations/` folder with timestamped migration folders. Paths are configured in **`mvc.config.json`**.

## Configuration (`mvc.config.json`)

- **`migrationsFolderPath`** — Relative path from the app root to the migration module directory (contains `index.php` and `migrations/`). Example: `./Migrations`.
- **`migrationsEnabled`** — `true` when the feature is enabled; `false` when disabled. New apps created with `mvc create-app` start with `migrationsEnabled: false` until you enable migrations.

## Enable the feature

From the project root (or set `--path` to your MVC app directory):

```bash
mvc migrations:enable [--path=<app-dir>] [--folder=<module-folder>]
```

- **`--path`** — MVC app root (directory containing `index.php` and `mvc.config.json`). Default: current directory.
- **`--folder`** — Name of the migration module folder under the app root. Default: `Migrations`.

This creates `<app>/<folder>/migrations/`, writes `<app>/<folder>/index.php`, and sets `migrationsFolderPath` and `migrationsEnabled: true` in `mvc.config.json`.

The alias **`mvc initialize-migrations`** runs the same logic as `migrations:enable` (deprecated).

## Disable the feature

```bash
mvc migrations:disable [--path=<app-dir>] [--remove-files] [--force]
```

This sets `migrationsEnabled: false` and clears `migrationsFolderPath`. By default **no files are deleted**.

- **`--remove-files`** — Delete the migration module directory from disk. Must be used together with **`--force`** to confirm.

## Database environment

The migration runner reads database settings from environment variables (registered by `Framework\Mvc\Migrations\MigrationBootstrap::registerFromEnvironment()` before `MigrationApp` runs — see the migration module’s `index.php` or the `mvc migrations:*` commands), for example:

- `MIGRATIONS_DATABASE_HOST`
- `MIGRATIONS_DATABASE_NAME`
- `MIGRATIONS_DATABASE_USER`
- `MIGRATIONS_DATABASE_PASSWORD`

Adjust these for your environment before running migrations.

## Daily commands

Run these from the repository root; use **`--app-path`** so the CLI resolves the leaf `migrations/` directory from `mvc.config.json`.

### Create a new migration

Creates a timestamped folder under the leaf `migrations/` directory with `0001_migration.sql` and `0001_migration.rollback.sql`:

```bash
mvc migrations:create --app-path=<app-dir>
```

Override the leaf directory if needed:

```bash
mvc migrations:create --path=<migrations-dir>
```

### Run all pending migrations

```bash
mvc migrations:run --app-path=<app-dir>
```

### Test one migration

Runs apply, rollback, and schema comparison for a single migration name (folder name):

```bash
mvc migrations:test --app-path=<app-dir> --migration=<migration-folder-name>
```

You can also run migrations directly via PHP against the module’s `index.php` (arguments are passed through to `MigrationApp`).

## Layout

```
<app>/
  index.php
  mvc.config.json
  <migration-module>/          # e.g. Migrations (from migrationsFolderPath)
    index.php                  # CLI entry for MigrationApp
    migrations/
      <YYYYMMDDhhmmss>/
        0001_migration.sql
        0001_migration.rollback.sql
```
