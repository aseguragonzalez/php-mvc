# CLI Reference

The `mvc` binary provides scaffolding, code generation, and development tooling. Run it via `vendor/bin/mvc`.

## App scaffolding

### `mvc create-app`

Scaffold a new MVC application structure.

```bash
vendor/bin/mvc create-app <path> --name=<AppName> --namespace=<Namespace>
```

| Option | Required | Description |
|--------|----------|-------------|
| `<path>` | Yes | Target directory for the new app. |
| `--name` | Yes | App class name (e.g. `MyApp`). Used for generated class names. |
| `--namespace` | Yes | PHP namespace (e.g. `App\\MyApp`). |

Generates: folder structure, `mvc.config.json`, `{Name}App.php`, `{Name}Bootstrap.php`, i18n file, and asset directories.

---

## Database migrations

### `mvc migrations:enable`

Enable the migrations module for an app.

```bash
vendor/bin/mvc migrations:enable [--path=<app-dir>] [--folder=<folder>]
```

| Option | Default | Description |
|--------|---------|-------------|
| `--path` | `.` | MVC app root (contains `index.php` + `mvc.config.json`). |
| `--folder` | `Migrations` | Migration module folder name under the app root. |

Creates the migration folder structure, writes `index.php`, and sets `migrationsEnabled: true` in `mvc.config.json`.

Alias: `mvc initialize-migrations` (deprecated).

---

### `mvc migrations:disable`

Disable the migrations module.

```bash
vendor/bin/mvc migrations:disable [--path=<app-dir>] [--remove-files] [--force]
```

| Option | Description |
|--------|-------------|
| `--path` | App root (default: current directory). |
| `--remove-files` | Delete migration module files from disk. Requires `--force`. |
| `--force` | Confirm destructive file removal. |

---

### `mvc migrations:create`

Create a new timestamped migration folder with blank forward and rollback scripts.

```bash
vendor/bin/mvc migrations:create --app-path=<app-dir>
# or override the migrations directory directly:
vendor/bin/mvc migrations:create --path=<migrations-dir>
```

---

### `mvc migrations:run`

Apply all pending migrations in chronological order.

```bash
vendor/bin/mvc migrations:run --app-path=<app-dir>
```

---

### `mvc migrations:test`

Test a single migration: apply → rollback → schema comparison.

```bash
vendor/bin/mvc migrations:test --app-path=<app-dir> --migration=<folder-name>
```

| Option | Description |
|--------|-------------|
| `--migration` | Timestamped folder name (e.g. `20260101120000`). |

---

## Authentication

### `mvc auth:enable`

Enable the authentication module; optionally generate SQL migrations for the default user/session tables.

```bash
vendor/bin/mvc auth:enable [--path=<app-dir>] [--skip-migrations]
```

| Option | Description |
|--------|-------------|
| `--skip-migrations` | Only set `authenticationEnabled: true`. No SQL files created. |

Prerequisite: the migrations module must be enabled.

---

### `mvc auth:disable`

Disable the authentication module.

```bash
vendor/bin/mvc auth:disable [--path=<app-dir>] [--skip-migrations]
```

Without `--skip-migrations`, generates a migration to drop the default auth tables.

---

## Background tasks

### `mvc initialize-background-tasks`

Scaffold the BackgroundTasks folder and stubs. Sets `backgroundTasksEnabled: false`.

```bash
vendor/bin/mvc initialize-background-tasks [--path=<app-dir>]
```

---

### `mvc background-tasks:enable`

Enable the background tasks module; optionally generate the `background_tasks` migration.

```bash
vendor/bin/mvc background-tasks:enable [--path=<app-dir>] [--skip-migrations]
```

| Option | Description |
|--------|-------------|
| `--skip-migrations` | Only set `backgroundTasksEnabled: true`. Use with a custom `TaskRepository`. |

Prerequisite: the migrations module must be enabled (unless `--skip-migrations`).

---

### `mvc background-tasks:disable`

Disable the background tasks module.

```bash
vendor/bin/mvc background-tasks:disable [--path=<app-dir>] [--skip-migrations]
```

---

### `mvc background-tasks:run`

Run the configured worker entrypoint (`BackgroundTasks/index.php`).

```bash
vendor/bin/mvc background-tasks:run [--app-path=<app-dir>] [--force] [-- <args>...]
```

| Option | Description |
|--------|-------------|
| `--app-path` | App root (default: current directory). |
| `--force` | Run even when `backgroundTasksEnabled` is `false`. |
| `-- <args>` | Arguments forwarded to `BackgroundTasks/index.php` (e.g. `-- --interval=60`). |

---

## Asset bundling

### `mvc watch-assets`

Watch source files and rebuild unminified JS/CSS bundles on change (development).

```bash
vendor/bin/mvc watch-assets --app-path=<app-dir>
```

---

### `mvc create-bundle`

Merge and minify all source JS/CSS into production bundles.

```bash
vendor/bin/mvc create-bundle --app-path=<app-dir>
```

---

## Deprecated commands

| Command | Replacement |
|---------|-------------|
| `mvc initialize-migrations` | `mvc migrations:enable` |
