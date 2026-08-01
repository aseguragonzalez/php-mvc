# CLAUDE.md

## Project

`aseguragonzalez/php-mvc` — Lightweight PHP 8.4+ MVC framework. Packagist library, no runtime dependencies beyond PSR interfaces. Binary: `bin/mvc` (also installed as `vendor/bin/mvc`).

PHP namespace: `PhpMvc\` (all source under `src/`).

## Workflow

Every change goes through three stages — never skip straight to code:

1. **Analyze and open the issue(s).** Understand the request, confirm the understanding
   with the requester, then open (or confirm) a GitHub issue with clear, testable
   acceptance criteria. A request that bundles unrelated concerns splits into more than
   one issue — one per independently shippable concern — rather than one issue standing in
   for all of them. No PR without at least one linked issue (`Closes #N` or `Relates to
   #N` — see `.claude/skills/gh-workflow/SKILL.md`).
2. **Plan before implementing.** Re-read the issue and draft an implementation plan that
   separates **code**, **tests**, and **documentation** as independent tracks built
   against the same agreed contracts (interfaces/signatures decided up front), so the
   tracks don't conflict with each other. A large or naturally incremental issue may be
   delivered through more than one PR; decide this up front rather than mid-implementation.
3. **Implement in parallel.** Execute the plan using parallel agents for code, tests, and
   documentation (see `.claude/agents/`) against the contracts fixed in step 2. Subagents
   don't share context with the main conversation or each other — include the fixed
   contract explicitly in every agent's prompt, don't assume they can infer it from one
   another's work.

Additional rules that apply throughout:

- All documentation and GitHub artifacts — issues, PRs, commit messages, code comments —
  are written in English, regardless of the language used in conversation, and are
  **direct and concise**: state the what/why/how, never the conversation or reasoning
  process that led to it. No narrative, no TL;DR filler.
- While analyzing any request, check whether nearby code could be improved. If so, do not
  bundle the improvement into the current change — open a separate issue for it (see the
  `boy-scout` skill).
- For bug reports, analyze the problem and propose a solution before opening an issue for
  it (see the `bug-triage` skill).
- PR review comments (yours or a bot reviewer's) are answered in English, as a reply in
  the same review-comment thread — never a new top-level PR comment.
- See `.claude/skills/gh-workflow/SKILL.md` for label taxonomy, identity, reviewer, and
  issue/PR mechanics.

## Commit and PR conventions

The type **must match the layer actually changed**, not just be "valid" Conventional
Commits — this repo squash-merges, so the PR title is the only text `semantic-release`
reads, and a mismatch either ships a spurious release or silently swallows one that should
have shipped:

- `fix:` / `feat:` / `feat!:` — only when `src/` behavior changed.
- `docs:` — changes limited to `docs/`, `README.md`, or `CLAUDE.md`.
- `ci:` — `.github/workflows/` or other pipeline-only changes.
- `chore:` / `build:` — tooling, `composer.json` build config, `.claude/` scaffolding,
  `.devcontainer/`, dependency bumps with no behavior change.
- `refactor:` — no behavior or public-API change; must never trigger a release.
- `test:` — test-only changes.

Every commit that reaches `main` must be GPG/SSH signed (verified) — see
`CONTRIBUTING.md` — "Commit signing" and `.claude/skills/gh-workflow/SKILL.md` —
"Identity" for how this applies to bot-authored commits specifically.

See `.claude/skills/gh-workflow/SKILL.md` for the full commit-type-to-release-impact
table, label taxonomy, identity split, reviewer rules, and PR release-readiness checks.

## Commands

```bash
make install      # composer install
make test         # phpunit
make audit        # composer audit (dependency vulnerability check)
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
make test                      # full suite (959 tests)
vendor/bin/phpunit --filter X  # single test or class
```

All 959 tests are unit tests — no real database required. Infrastructure tests (SQL repositories, schema executors) mock `PDO` via `createMock(\PDO::class)`.

## Quality checks before committing

```bash
make check   # cs + stan + test — must be green
```

## Validation environment

**All validation runs inside the devcontainer.** PHP and Composer are not available on the host. Start it once with:

```bash
devcontainer up --workspace-folder .
```

Then run any `make` target via:

```bash
devcontainer exec --workspace-folder . make <target>
```

Never report a quality check as passing without running it in the devcontainer first.

## Claude Code agents and skills for this repo

Agents under `.claude/agents/` implement the parallel code/test/docs tracks from
"Workflow" above, plus two cross-cutting agents:

- **`php-mvc-implementer`** — the `src/` track, against a fixed contract.
- **`php-mvc-test-writer`** — the `tests/` track, against the same contract.
- **`docs-aligner`** — the `docs/` track, against the same contract.
- **`boy-scout`** — finds and either executes or files refactoring opportunities.
- **`bug-analyst`** — investigates a reported defect and proposes a fix before any issue
  is opened.

Skills under `.claude/skills/`:

- **`gh-workflow`** — the full issue-first workflow: identity, label taxonomy, reviewer,
  commit-type table, and PR release-readiness checks.
- **`boy-scout`** — when/how to apply the boy-scout rule (execute inline vs. separate
  issue).
- **`bug-triage`** — the analyze → confirm → issue flow for bug reports.

`.claude/settings.json` (committed, shared across contributors) holds a conservative,
mostly-read-only permissions allowlist for these workflows: `devcontainer up`/
`devcontainer exec ... make|composer|pre-commit`, `git status/diff/log/show/branch`, and
read-only `gh`. Unlike the seedwork repos, there is no bare host-command fallback — PHP
and Composer are genuinely unavailable outside the devcontainer here, so all commands stay
wrapped. It deliberately excludes `git commit`/`push` and `gh issue`/`pr create`, which
always prompt. Personal or exploratory permissions belong in each contributor's own
`.claude/settings.local.json` instead.
