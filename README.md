# Mech Library

Locally-hosted searchable database and force-builder for **BattleTech Alpha Strike**, sourced from [masterunitlist.info](https://masterunitlist.info).

Built for personal use because the existing online filters are not flexible enough for serious force composition.

## Status

Pre-alpha. Phase 1 (scraper) in planning. See [`docs/PLAN.md`](docs/PLAN.md).

## Stack

PHP 8.5 · MySQL · [perplorm/perpl](https://packagist.org/packages/perplorm/perpl) ORM · Slim 4 · Twig · vanilla JS frontend.

## Quick start (will become real once phase 1 lands)

```bash
composer install
cp .env.example .env           # then edit DB credentials
bin/console db:migrate         # create schema
bin/console mul:scrape         # ~30 min, polite 1 req/s
bin/console mul:import         # raw JSON → DB
bin/console mul:images         # download unit images
# point Apache vhost at ./public
```

## Documentation

- [`CLAUDE.md`](CLAUDE.md) — ground rules for AI agents working on this repo. **Read first** if you are an agent.
- [`docs/PLAN.md`](docs/PLAN.md) — phased roadmap and handoff prompts.
- [`docs/MUL_API.md`](docs/MUL_API.md) — reverse-engineered masterunitlist.info JSON endpoints.
- [`docs/SCHEMA.md`](docs/SCHEMA.md) — DB schema and rationale (added in phase 1).

## Legal note

BattleTech, Alpha Strike, and all related marks are © Topps / Catalyst Game Labs. This project consumes publicly accessible JSON from masterunitlist.info for personal force-building only. The data is not redistributed.
