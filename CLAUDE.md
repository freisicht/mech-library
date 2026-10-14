# Mech Library — Agent Ground Rules

This file is loaded automatically by Claude Code at session start. **All agents working in this repo must read and follow it.** When in doubt, ask the user before deviating.

## Project in one sentence

A self-hosted PHP/MySQL web application that mirrors BattleTech Alpha Strike unit data from the Master Unit List (masterunitlist.battletech.com) into a searchable database with a force-builder on top.

## Tech stack — fixed, do not change without user approval

| Layer        | Choice                                                                |
| ------------ | --------------------------------------------------------------------- |
| Language     | PHP 8.3+ (CLI for scrapers, web for UI)                               |
| Database     | MySQL (existing local instance, see `.env`)                           |
| ORM          | **perplorm/perpl** (maintained Propel2 fork)                          |
| HTTP client  | guzzlehttp/guzzle                                                     |
| CLI          | symfony/console                                                       |
| Templates    | twig/twig                                                             |
| Routing      | slim/slim 4                                                           |
| Logging      | monolog/monolog                                                       |
| Env          | vlucas/phpdotenv                                                      |
| Tests        | phpunit/phpunit                                                       |
| Code style   | PSR-12, enforced via friendsofphp/php-cs-fixer                        |
| Static check | phpstan/phpstan (level 6 minimum)                                     |
| Frontend     | Plain HTML + vanilla/light JS. DataTables or Tabulator for the grid.  |
|              | **No React/Vue/SPA frameworks** unless the user explicitly approves.  |
| Build        | None for frontend in phase 1+2. Pure server-rendered pages.           |

## Repository layout

```
mech_library/
├── CLAUDE.md                  # this file
├── README.md                  # human entry point
├── composer.json
├── .env.example               # template; .env is gitignored
├── .gitignore
├── .editorconfig
├── docs/
│   ├── PLAN.md                # phased roadmap with handoff prompts
│   ├── MUL_API.md             # MUL data sources (snapshot + unit pages)
│   ├── SCHEMA.md              # DB schema rationale (created in phase 1)
│   └── decisions/             # short ADRs as we go (NNN-title.md)
├── bin/
│   └── console                # Symfony Console entry point
├── src/                       # PSR-4: App\
│   ├── Command/               # CLI commands (scrape, import, ...)
│   ├── Crawler/               # MUL HTTP client + data snapshot
│   ├── Importer/              # raw JSON → DB
│   ├── Model/                 # Perpl-generated + behaviors
│   └── Web/                   # Slim controllers, middleware
├── public/                    # web docroot (point Apache here)
│   ├── index.php
│   ├── images/units/          # downloaded MUL images, gitignored
│   └── assets/                # css, js
├── templates/                 # Twig
├── data/
│   ├── raw/                   # MUL cache (snapshot files, parsed unit pages), gitignored
│   └── migrations/            # Perpl migrations (hand-written SQL), see docs/SCHEMA.md
├── tests/
└── var/
    ├── cache/
    └── log/
```

## Hard rules for all agents

1. **Never re-research what is already documented.** Before exploring MUL endpoints, read `docs/MUL_API.md`. Before designing schema, read `docs/SCHEMA.md`. If a doc is missing or stale, update it as part of your task.
2. **Stay inside the repo.** Do not write files outside the repository checkout without asking.
3. **Never commit secrets.** `.env` is gitignored. Credentials go in `.env`, never in code or in `.env.example`.
4. **No scope creep.** If the user asked for the scraper, do not also touch the web UI. Park ideas in `docs/PLAN.md` under "Backlog".
5. **No invented dependencies.** Verify a Composer package exists on Packagist before adding it. The known-good list is in this file.
6. **Respect MUL.** All HTTP traffic to MUL (masterunitlist.battletech.com) goes through the central crawler client which enforces:
   - 10 s minimum delay between requests (robots.txt `Crawl-delay: 10`; configurable up, never below)
   - identifying User-Agent (`MechLibrary/<version> (private use; contact via README)`)
   - persistent disk cache under `data/raw/` keyed by query
   Never bypass this client. Never parallelise requests to MUL.
7. **Idempotent imports.** Re-running `mul:sync` on unchanged data must produce zero DB writes. Upserts are keyed on the MUL public id (`units.mul_uid`).
8. **PHP code style:** PSR-12, strict types (`declare(strict_types=1);` at top of every PHP file), no closing `?>` tag in pure-PHP files.
9. **Comments:** Default to none. A comment is justified only when the *why* is non-obvious. Do not write docblocks that just restate the signature.
10. **Tests:** Every Crawler/Importer class gets a unit test. Web controllers can be covered later; do not block phase 1 on web tests.

## Git workflow

- **Default branch:** `main`. Always green; never commit broken code there directly.
- **Feature branches:** `phase/<n>-<slug>` for phase work, `feat/<slug>` for smaller features, `fix/<slug>` for bug fixes, `docs/<slug>` for docs-only.
- **Commits:** Conventional Commits (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`). Imperative mood, ≤72 char subject, body explains *why* not *what* if non-trivial.
- **One logical change per commit.** No "WIP" or "stuff" commits on `main`.
- **Merge to main:** fast-forward or squash-merge after the phase is complete and tests pass. No merge commits with noise.
- **Tags:** `v0.1.0`, `v0.2.0` etc. at the end of each phase.
- **Never:** force-push to `main`, `--no-verify`, amend a pushed commit, commit `.env`, commit `vendor/`, commit `data/raw/` or `public/images/units/`.
- **Agents commit and fast-forward-merge to `main` automatically when `composer test` passes.** They never push to a remote unless the user asks. Full procedure (worktree setup, what counts as verification, when to stop and ask) is in [docs/AGENT_WORKFLOW.md](docs/AGENT_WORKFLOW.md) — read it before any task that will end in a commit.

## Definition of done (per phase)

A phase is done when:
- All tasks in `docs/PLAN.md` for that phase are checked.
- `composer test` passes (phpunit + phpstan + php-cs-fixer --dry-run).
- `docs/PLAN.md` is updated with anything learned (gotchas, follow-ups).
- A git tag `v0.<phase>.0` is set on `main`.

## Handoff to a new session

When the user starts a new session for the next task, point the new agent at:
1. This `CLAUDE.md` (auto-loaded).
2. The relevant section of `docs/PLAN.md` — each phase has a ready-to-paste handoff prompt.
3. Any phase-specific docs (`docs/MUL_API.md` for scraping, `docs/SCHEMA.md` for DB work).

The handoff prompts in `docs/PLAN.md` are the source of truth for what to build next.
