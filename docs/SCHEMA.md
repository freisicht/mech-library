# Mech Library — DB Schema

Schema rationale, not just DDL. The full DDL lives in `data/migrations/001_initial_schema.sql`.

## Database: `mech_library`

utf8mb4 / utf8mb4_unicode_ci throughout. All engines InnoDB for FK support.

---

## Lookup tables

Four small reference tables hold the foreign-key values the API returns as nested objects:

| Table          | Seeded by              | Notes                                    |
| -------------- | ---------------------- | ---------------------------------------- |
| `unit_types`   | importer (inline)      | Type.Id / Type.Name from each UnitObject |
| `technologies` | importer (inline)      | Technology.Id / Technology.Name          |
| `roles`        | importer (inline)      | Role.Id / Role.Name                      |
| `factions`     | `mul:seed-factions`    | parsed from `/Faction/Index` HTML        |
| `eras`         | `mul:seed-eras`        | parsed from `/Era/Index` HTML            |

Unit types, technologies, and roles are upserted on each import pass rather than pre-seeded — they come directly from the JSON and are stable enough that a lookup miss should fail loudly.

**Known type IDs:**
- `18` — BattleMech
- `45` — suspected OmniMech subtype (to be confirmed on first crawl)

---

## `units`

One row per unique MUL unit (`mul_id` is the upsert key). The table stores both
the raw API values and a small amount of derived/housekeeping state:

- **Raw Alpha Strike stats** (`bf_*`): direct mirrors of the API fields, including the API typo
  `BFDamageExtemeMin` (stored as `bf_damage_extreme_min` — the column name is normalised but
  the importer reads the misspelled key).
- **`bf_move`**: kept as a VARCHAR string (`"6\""`, `"8\"j"`) because the unit suffix encodes
  movement type (jump, VTOL, etc.) and parsing it would lose information.
- **`bf_abilities`**: raw comma-separated string. Never normalise this away — the canonical
  text matters for rules lookup and future ability filtering.
- **`image_path` / `image_hash`**: populated by `mul:images`, not `mul:import`. The hash is
  SHA-256 of the downloaded file; the path is relative to `public/`.

### Why not normalise factions / eras onto units?

The MUL QuickList response does not embed faction or era availability per unit — it uses them
as *request filters*. The `era_id` / `era_start` columns come from the response itself (the
era the unit was introduced in), not from a per-faction availability join. Full
faction×era availability is available via `/Unit/EraAvailability?id=...` but is out of
Phase 1 scope.

---

## `unit_abilities`

One row per parsed ability token in a unit's `BFAbilities` string. The four columns
(`short`, `medium`, `long`, `value`) cover the four token shapes documented in
`docs/MUL_API.md`:

| Token shape        | short | medium | long | value   |
| ------------------ | ----- | ------ | ---- | ------- |
| `CASE`             | NULL  | NULL   | NULL | NULL    |
| `IF1`              | NULL  | NULL   | NULL | `"1"`   |
| `AC2/2/-`          | 2     | 2      | NULL | NULL    |
| `HT3/2`            | NULL  | NULL   | NULL | `"3/2"` |

The `raw` column always holds the original token so the table can be rebuilt from it if the
parsing rules change.

---

---

## `armies`

Top-level grouping for formations. A named container. Currently has no constraints
(one formation per army, many formations per army — both valid).

---

## `formations`

One row per named force-builder formation. Key columns:

- **`army_id`** (nullable FK → armies): formations can exist standalone or under an army.
- **`formation_type`**: slug matching a key in `FormationTypeRegistry` (e.g. `battle-lance`,
  `battle-star`, `custom`). The rules engine uses this slug to look up constraints.
- **`pv_budget`**: optional PV ceiling the user has declared for this formation. Used to
  compute `pv_remaining` in the API response.
- **`is_active`** (TINYINT 0/1): at most one formation may be active at a time. The invariant
  is maintained by `FormationRepository::setActive()` (UPDATE all to 0, then UPDATE target to 1).
  MySQL lacks a partial unique index so the single-active constraint lives in application code.

---

## `formation_units`

Junction table. Columns:

- **`formation_id` / `unit_id`**: not unique. The same unit may appear several times in one
  formation (migration 005); each row is one model on the table, addressed by its own `id`.
- **`sort_order`**: append-only counter incremented by `FormationRepository::addUnit()`.
  Drives the display order in the force panel.

The ON DELETE CASCADE on `formation_id` and `unit_id` ensures rows are cleaned up
automatically when either parent is deleted.

---

## Migration strategy

Plain SQL files in `data/migrations/`, numbered `NNN_slug.sql`, applied in order by
`bin/console db:migrate` against the database named in `DB_NAME` (the files contain no `USE`
statement). Applied files are recorded in `schema_migrations`, so the command runs only what
is new and belongs in every deploy. The files themselves are not idempotent: never run them
a second time by hand.

A database that was migrated by hand before `schema_migrations` existed needs a one-time
`bin/console db:migrate --baseline=NNN`, where NNN is the last file already in it. The
runner splits files on a `;` at line end and drops `-- ` comments; keep statements in that
shape (no stored procedures, no `DELIMITER`). MySQL DDL is not transactional, so a failing
file can leave a half-applied state that has to be fixed by hand before re-running.

Perpl's Propel2 migration tooling is not used; plain SQL is adequate and easy to review.

| File                    | Adds                                                        |
| ----------------------- | ----------------------------------------------------------- |
| `001_initial_schema.sql`| units, abilities and lookup tables                          |
| `002_force_builder.sql` | `armies`, `formations`, `formation_units`                   |
| `003_auth.sql`          | `users`, `armies.required_mm_channel_id` (see `docs/AUTH.md`) |
| `004_auth_providers.sql`| `users.provider`/`external_id`/`password_hash`, channels renamed to groups |
| `005_formation_unit_duplicates.sql` | drops `uq_formation_unit`, allows the same unit twice |
| `006_mul_relaunch.sql`  | `units.mul_uid` (new MUL public id), `mul_id` nullable, `tonnage` as FLOAT |

When the schema changes, add a new numbered file, update `src/Model/schema.xml` and
regenerate the models with `vendor/bin/perpl model:build`.
