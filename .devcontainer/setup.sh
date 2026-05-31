#!/usr/bin/env bash
set -euo pipefail

# PHP dependencies
composer install

# Python environment via uv
uv venv /home/vscode/.venv
uv pip install --python /home/vscode/.venv/bin/python -r requirements.txt

# Activate venv in new shell sessions
grep -qF '/home/vscode/.venv/bin/activate' ~/.bashrc \
    || echo 'source /home/vscode/.venv/bin/activate' >> ~/.bashrc
grep -qF '/home/vscode/.venv/bin/activate' ~/.zshrc 2>/dev/null \
    || echo 'source /home/vscode/.venv/bin/activate' >> ~/.zshrc

# Configure Git SSH signing when key is mapped via docker-compose.override.yml
if [ -f /home/vscode/.ssh/id_ed25519 ]; then
    git config --global gpg.format ssh
    git config --global user.signingkey /home/vscode/.ssh/id_ed25519.pub
    git config --global commit.gpgsign true
    echo '[setup] Git SSH commit signing configured.'
fi
