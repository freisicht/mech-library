# Mech Library

Self-hosted searchable database and force builder for **BattleTech Alpha Strike**, fed from the
public unit data on [masterunitlist.info](https://masterunitlist.info) (MUL).

Built because the filters on MUL are not flexible enough for serious force composition.

![Unit list with filter matrix and formation panel](docs/design/preview.png)

## Features

- **Unit table** with combined filters: type, PV range, movement (incl. jump), TMM, damage at
  S/M/L, role, tech base, era, intro year and special abilities with values (`IF ≥ 1`, `FLK`, …),
  AND/OR mode.
- **Unit detail pages** with parsed ability breakdown and the MUL image.
- **Force builder:** armies and formations with live PV totals and rule checks for the Alpha
  Strike formation types (Battle Lance, Fire Support Lance, Heavy Battle Star, …).
- **Print view and PDF** with Alpha Strike style unit cards.
- **Login via Mattermost** (OAuth2). Armies can be limited to members of a Mattermost channel,
  so one instance can serve several gaming groups. See [`docs/AUTH.md`](docs/AUTH.md).

## Requirements

- PHP 8.3 or newer with `curl`, `json`, `mbstring`, `pdo_mysql`
- MySQL 8 (or compatible)
- Composer
- A web server that can serve `public/` as the document root of its own host
  (the app expects to live at `/`, not in a subdirectory)
- A Mattermost server with an OAuth2 app for login

## Setup

```bash
composer install
cp .env.example .env    # DB credentials and Mattermost settings
```

Create the database and apply the migrations in order:

```bash
mysql -e "CREATE DATABASE mech_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
for f in data/migrations/*.sql; do mysql mech_library < "$f"; done
```

Fill it from MUL. The crawler waits at least one second between requests and caches every
response under `data/raw/`, so a re-run only hits MUL for what is missing.

```bash
bin/console mul:seed-eras
bin/console mul:seed-factions
bin/console mul:scrape          # BattleMechs by default, --types=18,... for more
bin/console mul:import          # data/raw/*.json into MySQL, idempotent
bin/console mul:images          # unit images into public/images/units/
```

Then point a virtual host at `public/`. For Apache, `public/.htaccess` handles the rewrites
(needs `AllowOverride All` and `mod_rewrite`). Register the OAuth app in Mattermost as
described in [`docs/AUTH.md`](docs/AUTH.md).

## Development

```bash
composer test           # php-cs-fixer (dry run), PHPStan level 6, PHPUnit
```

The web tests need a MySQL database with the schema applied; they create and remove their own
fixture units. The GitHub Actions workflow in `.github/workflows/test.yml` shows the full setup.

Stack: Slim 4, Twig, [Perpl](https://packagist.org/packages/perplorm/perpl) (Propel2 fork),
Symfony Console, Guzzle, Tabulator and Pico CSS. No frontend build step.

## Documentation

- [`docs/MUL_API.md`](docs/MUL_API.md): the masterunitlist.info JSON endpoints as far as we
  understand them
- [`docs/SCHEMA.md`](docs/SCHEMA.md): database schema and rationale
- [`docs/AUTH.md`](docs/AUTH.md): Mattermost login and per-army channel gating
- [`docs/PLAN.md`](docs/PLAN.md): roadmap, implementation notes and backlog
- [`docs/decisions/`](docs/decisions/): short architecture decision records
- [`CLAUDE.md`](CLAUDE.md) and [`docs/AGENT_WORKFLOW.md`](docs/AGENT_WORKFLOW.md): ground rules
  for AI coding agents. Most of this project was written with Claude Code.

## Please be nice to MUL

masterunitlist.info is run by volunteers. Keep the request delay at one second or more, keep the
identifying User-Agent, and do not run the scraper more often than you need to. The cache in
`data/raw/` exists so you rarely have to.

## Legal

Mech Library is an unofficial fan project. It is not affiliated with, endorsed by or sponsored
by Topps, Catalyst Game Labs or masterunitlist.info. BattleTech, Alpha Strike and related marks
are trademarks of their respective owners.

This repository contains no unit data or images. Each installation fetches them from MUL for
its own use.

The code is released under the [MIT License](LICENSE). Bundled third-party assets keep their own
licenses, see [`THIRD_PARTY.md`](THIRD_PARTY.md).
