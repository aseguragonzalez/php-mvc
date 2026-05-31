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

## Commit signing

All commits must be **GPG or SSH signed** (verified). This is required to maintain the integrity of the public package history.

### Setting up SSH signing inside the dev container

The dev container does not include your SSH key by default. To map your local key into the container (bind mount — not a copy):

1. Copy the example override file:

   ```bash
   cp .devcontainer/docker-compose.override.yml.example \
      .devcontainer/docker-compose.override.yml
   ```

2. Edit `.devcontainer/docker-compose.override.yml` if your signing key has a different name or location. The default mounts `~/.ssh` read-only:

   ```yaml
   services:
     app:
       volumes:
         - ~/.ssh:/home/vscode/.ssh:ro
   ```

3. Rebuild the dev container. The `postCreateCommand` detects `/home/vscode/.ssh/id_ed25519` and automatically sets:

   ```
   gpg.format = ssh
   user.signingkey = /home/vscode/.ssh/id_ed25519.pub
   commit.gpgsign = true
   ```

`.devcontainer/docker-compose.override.yml` is listed in `.gitignore` — your local key path is never committed.

## Reporting security issues

Please do **not** open a public GitHub issue for security vulnerabilities. See [SECURITY.md](SECURITY.md) for the responsible disclosure process.
