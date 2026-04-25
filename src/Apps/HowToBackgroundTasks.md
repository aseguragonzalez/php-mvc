# How to use MVC background tasks (CLI)

The MVC framework provides a **queued task** pipeline (`SqlTaskRepository` and handlers) for work that should run outside the web request. The CLI records intent in **`mvc.config.json`**, and when you use the **default SQL-backed storage**, generates matching **database migrations**.

## Configuration (`mvc.config.json`)

- **`backgroundTasksEnabled`** — `true` after `mvc background-tasks:enable`; `false` after `mvc background-tasks:disable`. New apps from `mvc create-app` start with `false`.

- **`backgroundTasksFolderPath`** — Relative path from the app root to the BackgroundTasks module (contains `index.php`). Set by `mvc initialize-background-tasks` (default `./BackgroundTasks`).

- **`backgroundTasksPollIntervalSeconds`** — Optional integer `> 0` to run the worker in a **loop** inside one PHP process (sleep between batches). When `0` or omitted, the entrypoint runs **one batch** per invocation unless you pass `--interval` on the CLI (see below).

The flag **`backgroundTasksEnabled`** is enforced by generated **`BackgroundTasks/index.php`**: direct execution exits with an error until you enable the feature.

## CLI: scaffold vs enable

1. **`mvc initialize-background-tasks`** — Creates the `BackgroundTasks/` folder, stubs, and sets `backgroundTasksFolderPath`. Sets `backgroundTasksEnabled` to `false`.

2. **`mvc background-tasks:enable`** — Sets `backgroundTasksEnabled: true` and, unless you use `--skip-migrations`, adds a migration that creates the `background_tasks` table.

3. **`mvc background-tasks:disable`** — Sets `backgroundTasksEnabled: false` and adds a migration that drops `background_tasks` (inverse rollback recreates it).

Prerequisite for default migrations: the **migrations** module must exist and be enabled (`mvc migrations:enable`). See [How to use MVC database migrations](./HowToMigrations.md).

### Enable

```bash
mvc background-tasks:enable [--path=<app-dir>] [--skip-migrations]
```

- **`--path`** — App root (directory containing `index.php` and `mvc.config.json`). Default: current directory.
- **`--skip-migrations`** — Only set `backgroundTasksEnabled: true`. Use when you use a **custom** `TaskRepository` or non-SQL storage and do not need the default table.

If background tasks are **already** enabled, the command exits successfully and does **not** add another migration.

### Disable

```bash
mvc background-tasks:disable [--path=<app-dir>] [--skip-migrations]
```

If already disabled, the command exits successfully and does **not** add a migration.

Apply pending migrations when ready:

```bash
mvc migrations:run --app-path=<app-dir>
```

## CLI: run the worker (`mvc background-tasks:run`)

Run the configured entrypoint via **`mvc.config.json`** (resolves `BackgroundTasks/index.php` without hard-coding paths):

```bash
mvc background-tasks:run [--app-path=<app-dir>] [--force] [--] [<args>...]
```

- **`--app-path`** — MVC app root (default: current directory).
- **`--force`** — Run even when `backgroundTasksEnabled` is `false` (operators only).
- Arguments after **`--`** are forwarded to the app’s `index.php` (for example `--interval=60`).

## Cron vs in-process loop

- **Cron / external scheduler:** run **`mvc background-tasks:run`** (or `php BackgroundTasks/index.php`) on a schedule. Each invocation processes **one batch** (default).

- **Long-lived worker:** pass **`--interval=<seconds>`** to `index.php` (via `mvc background-tasks:run -- --interval=60`) **or** set **`backgroundTasksPollIntervalSeconds`** in `mvc.config.json`. The process runs batches in a loop with `sleep()` between iterations. On POSIX systems, **SIGINT** / **SIGTERM** stop the loop after the current batch when the `pcntl` extension is available.

## Default providers and migrations

Without `--skip-migrations`, the CLI creates a timestamped folder under your migration module’s `migrations/` directory. The forward script creates the `background_tasks` table expected by `Framework\Mvc\BackgroundTasks\Infrastructure\SqlTaskRepository`.

**Brownfield:** If you already applied the same DDL elsewhere, use **`--skip-migrations`** on enable/disable or reconcile duplicate migrations manually.

## Custom `TaskRepository` or storage

1. Run **`mvc background-tasks:enable --skip-migrations`** (and **`background-tasks:disable --skip-migrations`** when turning off) so only `mvc.config.json` changes.
2. Register your own `TaskRepository` implementation in the BackgroundTasks bootstrap (composition root) / DI setup instead of the default SQL wiring.
3. You do not need the `background_tasks` table unless your implementation uses it.

## Database and logging (environment variables)

The **composition root** for your worker (for example `BackgroundTasksBootstrap` next to `index.php`, or the generated `*BackgroundTasksBootstrap` from `mvc initialize-background-tasks`) registers **database** and **logging** from environment variables (for example `BACKGROUND_TASKS_DATABASE_HOST`, `BACKGROUND_TASKS_DATABASE_NAME`, `BACKGROUND_TASKS_DATABASE_USER`, `BACKGROUND_TASKS_DATABASE_PASSWORD`, `BACKGROUND_TASKS_LOG_LEVEL`, or app-prefixed names in the generated bootstrap). Edit that bootstrap class if you need different configuration sources.

The runnable class is `Framework\Mvc\BackgroundTasks\BaseBackgroundTasksApp`: it only processes batches; it does not read environment variables itself.

## Related documentation

- [How to use MVC database migrations](./HowToMigrations.md) — `migrations:enable`, `migrations:run`, env vars.
- [How to Create a New MVC App (CLI)](../Cli/HowToCreateApp.md) — scaffold and `mvc.config.json` defaults.
- [How to enable MVC authentication](../Functional/HowToAuthentication.md) — similar enable/disable + migration pattern.
