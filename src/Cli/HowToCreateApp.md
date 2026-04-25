# How to Create a New MVC App (CLI)

The MVC framework includes a small scaffolding tool to create a new app structure.

## Create the app scaffold

Run:

```bash
mvc create-app <path> --name=<AppName> --namespace=<Namespace>
```

Example:

```bash
mvc create-app ./src/Ports/MyApp --name=MyApp --namespace=App\\Ports\\MyApp
```

## What gets generated

When you run `mvc create-app`, the CLI will:

1. Create the MVC app folder structure under `<path>`.
2. Generate `mvc.config.json` in the app root.
3. Generate `{AppName}Bootstrap.php` — composition root that registers settings, logging, and PDO on the container (called from `index.php` before `{AppName}App` runs).
4. Create i18n file `assets/i18n/en.json`.
5. Create asset directories:
   - `assets/scripts`
   - `assets/styles`

## `mvc.config.json`

The scaffolded config file stores the locations and bundle filenames the framework templates and features will use, including:

- JavaScript assets path (default: `./assets/scripts`)
- Main JavaScript bundler name (default: `main.min.js`)
- CSS assets path (default: `./assets/styles`)
- Main CSS bundler name (default: `main.min.css`)
- Development bundle names: `devMainJsBundler` (default: `main.js`), `devMainCssBundler` (default: `main.css`)
- `useDevAssets` (`false` by default; set `true` while using `mvc watch-assets` so layouts load the dev bundles)
- `assetRoutes` — ordered groups of `js` / `css` source paths (relative to the app root); used by `mvc watch-assets` and `mvc create-bundle`
- i18n base path (default: `./assets/i18n`)
- `migrationsFolderPath` (empty by default)
- `migrationsEnabled` (`false` by default; set `true` when you run `mvc migrations:enable`)
- `backgroundTasksFolderPath` (empty by default)
- `authenticationEnabled` (`false` by default; set `true` when you run `mvc auth:enable`)

For CSS/JS bundling CLI and `assetRoutes` details, see [How to bundle CSS and JavaScript (MVC CLI)](../Web/HowToAssets.md).

For migrations workflow and CLI commands, see [How to use MVC database migrations](../Apps/HowToMigrations.md).

For authentication CLI and middleware wiring, see [How to enable MVC authentication and authorization (CLI)](../Functional/HowToAuthentication.md).
