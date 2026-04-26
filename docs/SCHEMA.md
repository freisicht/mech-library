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

## Migration strategy

Phase 1 uses plain SQL files in `data/migrations/` numbered `NNN_slug.sql`. The `db:migrate`
command (to be wired up once Perpl models exist) tracks which migrations have been applied in
a `schema_migrations` table. No ORM migration runner in Phase 1 — Perpl's Propel2 migration
tooling is available but adds complexity; plain SQL is adequate and easy to review.
