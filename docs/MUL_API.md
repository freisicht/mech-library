# masterunitlist.info — API notes

Reverse-engineered from the live site on 2026-04-26. There is no official public API. Endpoints are the same ones the site's own UI calls.

## Base

`http://masterunitlist.info/`

We deliberately use plain HTTP. The server presents an Azure wildcard cert
(`CN=*.azurewebsites.net`) that does not cover `masterunitlist.info`, so any
strict TLS client — including default Guzzle — fails with `cURL error 60`.
HTTP works without a redirect and the data is public/read-only, so the
trade-off is acceptable. If they fix their cert, switch `MUL_BASE_URL` in
`.env` back to `https://`.

## Primary endpoint: `/Unit/QuickList`

Returns JSON of units matching the supplied filters. Used by the site's main browse table.

### Request

`GET /Unit/QuickList?<filters>`

Filters observed in the wild (all optional; combine with `&`):

| Param        | Example       | Notes                                                 |
| ------------ | ------------- | ----------------------------------------------------- |
| `Name`       | `Atlas`       | substring match on full name                          |
| `Types`      | `18`          | unit-type id; `18` = BattleMech                       |
| `MinTons`    | `100`         | inclusive                                             |
| `MaxTons`    | `100`         | inclusive — set both equal for exact-tonnage bucket   |
| `MinPV`      | `10`          | Alpha Strike point value, inclusive                   |
| `MaxPV`      | `12`          | inclusive                                             |
| `Factions`   | `12,7`        | comma-separated faction ids                           |
| `Eras`       | `13`          | comma-separated era ids                               |
| `Rules`      | `Standard`    | also `Advanced`, `Experimental`, …                    |

Calling with **no** filters returns an empty `Units` array. At least one filter is required. Calling with too-broad filters (e.g. `Types=18` alone for all Mechs) returns **HTTP 500** with the message `The length of the string exceeds the value set on the maxJsonLength` — the server cannot serialise the result.

**Working strategy:** iterate per `(Type, Tonnage)` bucket. For Mechs that gives 20 buckets (5–100 t in 5-t steps); each bucket is well within the size limit (largest seen: 50 t → 301 units / ~300 KB).

### Response shape

```json
{
  "Units": [ { ...UnitObject... } ],
  "Search": { ...echoed filters... },
  "Crumbs": [ ... ]
}
```

### UnitObject — fields relevant to Alpha Strike

Real example (`/Unit/QuickList?Name=Atlas+AS7-D&MaxPV=200`, first hit):

```json
{
  "Id": 142,
  "Name": "Atlas AS7-D-DC",
  "Class": "Atlas",
  "Variant": "AS7-D-DC",
  "Tonnage": 100,
  "BattleValue": 1858,
  "Cost": 10406000,
  "Rules": "Advanced",
  "DateIntroduced": "2776",
  "EraId": 10,
  "EraStart": 2571,
  "ImageUrl": "https://i.ibb.co/VtHKQ7Q/atlas-rg.png",
  "IsPublished": true,
  "Release": 1.0,

  "Technology": { "Id": 1, "Name": "Inner Sphere", ... },
  "Type":       { "Id": 18, "Name": "BattleMech",  ... },
  "Role":       { "Id": 108, "Name": "Juggernaut", ... },

  "TROId": 204, "TRO": "MWC",
  "RSId": 241,  "RS":  "RS:3039u",

  "BFType":         "BM",       // Alpha Strike unit type
  "BFSize":         4,
  "BFMove":         "6\"",      // string, may include "j" for jump (e.g. "8\"j")
  "BFTMM":          0,
  "BFArmor":        10,
  "BFStructure":    8,
  "BFThreshold":    0,
  "BFDamageShort":  5, "BFDamageShortMin":  false,
  "BFDamageMedium": 5, "BFDamageMediumMin": false,
  "BFDamageLong":   1, "BFDamageLongMin":   false,
  "BFDamageExtreme":0, "BFDamageExtemeMin": false,  // sic: typo "Exteme" in API
  "BFOverheat":     0,
  "BFPointValue":   52,
  "BFAbilities":    "AC2/2/-,IF1,MHQ1"   // comma-separated, mixed format
}
```

**Note the API typo:** `BFDamageExtemeMin` (missing `r`). Mirror it in the importer or alias on read; do not silently rename or you'll mismatch when re-fetching.

### `BFAbilities` parsing

Comma-separated list, no spaces. Tokens take several shapes:

| Form              | Examples           | Meaning                                          |
| ----------------- | ------------------ | ------------------------------------------------ |
| `<TAG>`           | `CASE`, `ECM`      | flag-only ability                                |
| `<TAG><N>`        | `MHQ1`, `IF1`      | tag with single integer rating                   |
| `<TAG><S>/<M>/<L>`| `AC2/2/-`, `LRM1/1/1` | per-range damage; `-` = no damage at that range |
| `<TAG><N>/<R>`    | `HT3/2`            | rarer composite forms — store raw string too     |

Recommendation: store `BFAbilities` raw (canonical text) AND parse into a side table `unit_abilities (unit_id, tag, short, medium, long, value)` for queryability. Always keep the raw — the rules around ability text evolve.

## Other endpoints

- `GET /Unit/Details/{id}` — HTML page (not JSON). Holds extra fluff/availability info if we ever need it.
- `GET /Unit/Autocomplete?term=...` — JSON, name suggestions.
- `GET /Unit/EraAvailability?id=...` — JSON, per-era availability matrix.
- `GET /Unit/MoreComplete?...` — variant of QuickList with more fields, untested.
- `GET /Faction/Index`, `GET /Era/Index` — HTML lists. Parse once to seed the local lookup tables; faction/era ids are stable.

## Images

`ImageUrl` points at hosts like `i.ibb.co/<hash>/<slug>.png`, `s3-us-west-2.amazonaws.com/mul-images/...`. Download once, store under `public/images/units/{mul_id}.<ext>` (extension from URL). Hash the file content; if unchanged on re-fetch, skip rewriting.

Some units have `ImageUrl == null` or a placeholder — handle gracefully.

## Etiquette / rate limit

There is no documented rate limit. The crawler must:
- Sleep ≥ 1.0 s between requests (configurable up, never below).
- Send a clear identifying `User-Agent`.
- Cache every response to disk; subsequent runs hit the cache.
- Run sequentially. No parallelism against this host.

## Counts (2026-04-26 sampling)

- Tonnage 20 t Mechs: 148
- Tonnage 50 t Mechs: 301
- Tonnage 80 t Mechs: 227
- Tonnage 100 t Mechs: 233
- Total Mechs (all tonnages): in the low thousands. Full crawl ≈ 20–25 requests, ~30 s of HTTP wall time at 1 rps.
