# How to bundle CSS and JavaScript (MVC CLI)

The MVC CLI can merge your app’s source styles and scripts into single files for the browser. Use **`mvc watch-assets`** during development (unminified, fast feedback) and **`mvc create-bundle`** when you want production-ready minified files.

## Configuration (`mvc.config.json`)

Asset bundling is driven entirely by your app’s **`mvc.config.json`** (next to `index.php`).

### Output locations and filenames

- **`jsAssetsPath`** / **`cssAssetsPath`** — Directories where bundle files are written (relative to the app root), e.g. `./assets/scripts` and `./assets/styles`.
- **`mainJsBundler`** / **`mainCssBundler`** — Filenames for **minified** output from `mvc create-bundle` (e.g. `main.min.js`, `main.min.css`). These are what layouts typically reference in production.
- **`devMainJsBundler`** / **`devMainCssBundler`** — Filenames for **unminified** output from `mvc watch-assets` (defaults: `main.js`, `main.css`).

### Source lists: `assetRoutes`

**`assetRoutes`** is an ordered array of groups. Each group represents a logical area (for example “global layout” vs “dashboard”). The CLI walks the array in order and merges all `js` paths into one JavaScript bundle and all `css` paths into one CSS bundle. Duplicate paths are included only once (first occurrence wins).

Example:

```json
{
  "assetRoutes": [
    {
      "label": "base",
      "js": ["assets/scripts/core.js"],
      "css": ["assets/styles/root.css", "assets/styles/layout.css"]
    },
    {
      "label": "dashboard",
      "js": ["assets/scripts/dashboard.js"],
      "css": ["assets/styles/dashboard.css"]
    }
  ]
}
```

You may omit **`js`** or **`css`** on a group if that side is empty. At least one non-empty list across all groups is required.

### Serving dev bundles in HTML: `useDevAssets`

Layouts use `{{mainJsBundler}}` and `{{mainCssBundler}}` (via `UiAssetsSettings`). Set **`useDevAssets`: `true`** while you run `mvc watch-assets` so the app requests **`devMainJsBundler`** / **`devMainCssBundler`** instead of the minified names. Set it back to **`false`** in production (and run **`mvc create-bundle`** before deploy) so pages load the minified files.

## Commands

Run from any directory; point at the app root with **`--app-path`** (defaults to the current directory). The app root must contain **`index.php`**.

### Watch (development)

```bash
mvc watch-assets --app-path=./src/Ports/MyApp
```

Polls source files, rebuilds **unminified** bundles when they change. Does **not** write minified files.

### Production bundle

```bash
mvc create-bundle --app-path=./src/Ports/MyApp
```

Merges and **minifies** into **`mainJsBundler`** and **`mainCssBundler`**.

## Typical workflow

1. Configure **`assetRoutes`** and output paths in **`mvc.config.json`**.
2. Set **`useDevAssets`** to **`true`** for local work.
3. In one terminal: **`mvc watch-assets --app-path=<your-app>`**.
4. In another: run your PHP server and exercise the app; refresh the browser after saves.
5. Before release: set **`useDevAssets`** to **`false`**, run **`mvc create-bundle --app-path=<your-app>`**, deploy.

## Scaffolded apps

Running **`mvc create-app`** generates **`assets/scripts/main.js`**, **`assets/styles/main.css`**, and a **`default`** entry under **`assetRoutes`** so **`watch-assets`** and **`create-bundle`** work out of the box.

## See also

- [How to Create a New MVC App (CLI)](../Cli/HowToCreateApp.md) — scaffold and `mvc.config.json` overview
- [How to use MVC database migrations](../Apps/HowToMigrations.md)
