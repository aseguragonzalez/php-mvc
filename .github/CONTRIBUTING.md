# Contributing

Thank you for considering contributing to `aseguragonzalez/php-mvc`.

## Requirements

- Docker and the [Dev Containers CLI](https://github.com/devcontainers/cli) (`devcontainer` command), **or** VS Code with the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers).

PHP and Composer are **not** required on the host — they run exclusively inside the dev container.

## Getting started

Clone the repository and start the dev container:

```bash
git clone https://github.com/aseguragonzalez/php-mvc.git
cd php-mvc
devcontainer up --workspace-folder .
```

Install dependencies inside the container:

```bash
devcontainer exec --workspace-folder . make install
```

## Development workflow

All make targets must be run inside the dev container. Running them directly on the host will fail because PHP and Composer are only available inside the container.

```bash
devcontainer exec --workspace-folder . make <target>
```

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

## Submitting changes

1. Fork the repository and create a branch from `main`.
2. Add or update tests for any behaviour change.
3. Run `make check` and ensure it passes cleanly.
4. Open a pull request with a clear description of the change and its motivation.

## Code style

This project follows the rules defined in `.php-cs-fixer.dist.php`. Run `make cs-fix` before committing.

## Reporting security issues

Please do **not** open a public GitHub issue for security vulnerabilities. See [SECURITY.md](SECURITY.md) for the responsible disclosure process.
