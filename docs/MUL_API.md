# Master Unit List: data sources

Researched 2026-10-13. MUL relaunched in autumn 2026 as **masterunitlist.battletech.com**.
The old site (`masterunitlist.info`) now redirects its start page there; the old JSON endpoint
`/Unit/QuickList` and the HTML lists `/Era/Index`, `/Faction/Index` answer **404**. Everything
below is about the new site.

## Status 2026-10-14: Cloudflare challenge

Since 14 October 2026 `/data/*` and the start page answer non-browser clients with **403** and
`cf-mitigated: challenge`, from a home connection and from a server alike. `robots.txt` still
answers 200. `mul:sync` cannot fetch anything new until this changes. Do not work around the
challenge; the MUL team is being asked how tools like this should access the data.

## Etiquette

`robots.txt` sets `Crawl-delay: 10` and disallows only `/factions/*?`, `/eras/*?` and the RAT
pages. Unit pages (`/units/*`) and `/data/*` are allowed. The crawler therefore:

- waits at least **10 s** between requests (`MulClient::MIN_DELAY_MS`, not configurable lower),
- sends `MechLibrary/<version> (private use; https://github.com/freisicht/mech-library)`,
- caches everything it can on disk and never runs in parallel.

A full sync of all 8,711 units costs about 24 h of wall time. That is the price of the delay;
do not try to shorten it.

## Data snapshot: `/data/manifest.json`

The search page loads the whole catalogue client-side. The manifest names the files, each with
a content hash in the filename, so they can be cached forever and the manifest alone tells
whether anything changed:

```json
{"files": {"units": "units.3eeeb96cd40a.json", "unit_types": "unit_types.2f2b7a042a42.json",
           "unit_sub_types": "…", "roles": "…", "technologies": "…", "eras": "…",
           "factions": "…", "abilities": "…", "availability": "…", "manufacturers": "…"},
 "unit_keys": {…}, "availability_keys": {…}}
```

Files are fetched from `/data/<name>`. The manifest also documents the short keys.

### `units` (≈1.2 MB, one entry per unit)

```json
{"id":"000RM4HYJ9","n":"Black Knight","m":"BL-7-KNT","t":1,"st":4,"r":3,"te":1,
 "ton":75,"pv":34,"bv":1443,"iy":2809,"ie":3,"ab":[18],"hp":1}
```

| Key  | Meaning                                                              |
| ---- | -------------------------------------------------------------------- |
| `id` | public uid, 10 characters. **Replaces the old integer `Id`.**         |
| `n`, `m` | name (class) and model (variant); `m` may be null                |
| `dt` | display title, only for ships with a hull type (`"Aegis class Heavy Cruiser"`) |
| `t`, `st` | type and sub-type id                                            |
| `r`, `te` | role and technology id                                          |
| `ton` | tonnage, fractional for Battle Armor (`1.5`), up to tens of thousands for ships |
| `pv`, `bv` | Alpha Strike point value, Battle Value                         |
| `iy`, `ie` | intro year, intro era id                                       |
| `ab` | ability tag ids (codes only, no values)                               |

**The snapshot has no Alpha Strike stats** (move, damage, armor, structure, ability values).
Those exist only on the unit pages.

### Lookup files

All lookup ids were renumbered in the relaunch. Local tables keep the old ids, so the importer
matches by **name** (`LookupResolver`, en dash and hyphen treated alike).

Unit types (`t`), as of 2026-10-13, with unit counts:

| id | Name             | Units | AS type      |
| -- | ---------------- | ----: | ------------ |
| 1  | BattleMech       | 4053  | BM           |
| 2  | Battle Armor     | 1199  | BA           |
| 3  | OmniVehicle      |  121  | CV           |
| 4  | Combat Vehicle   | 1127  | CV           |
| 5  | Fighter Craft    |  575  | AF, CF (sub-type "Conventional Fighter") |
| 6  | Infantry         |  441  | CI           |
| 7  | IndustrialMech   |  197  | IM           |
| 8  | ProtoMech        |   86  | PM           |
| 9  | Support Vehicle  |  374  | SV           |
| 10 | Aerospace Craft  |  437  | SC, DS, JS, WS, SS by sub-type |
| 11 | Advanced Support |   72  |              |
| 12 | Buildings        |   29  |              |

The AS type is not published anywhere; `SnapshotUnitMapper::bfType()` derives it from type and
sub-type.

## Unit page: `/u/{id}`

Redirects to `/units/{slug}`. Server-rendered HTML; the Alpha Strike block looks like this
(whitespace trimmed):

```html
<h3 class="u-colh">Alpha Strike Stats</h3>
<div class="u-statgrid">
  <div><div class="k">PV</div><div class="v">34</div></div>
  <div><div class="k">Size</div><div class="v">3</div></div>
  <div><div class="k">Move</div><div class="v">8&quot;</div></div>
  <div><div class="k">TMM</div><div class="v">1</div></div>
  <div><div class="k">Armor</div><div class="v">6</div></div>
  <div><div class="k">Struct</div><div class="v">6</div></div>
</div>
<div class="u-abilrow">
  <span class="aschip">DMG 3/3/1/0</span>
  <span class="aschip">OV 2</span>
  <span class="aschip">ENE <span class="an">Energy</span></span>
  <span class="aschip">Brawler</span></div>
```

- `—` stands for "no value": TMM of aerospace units, armor of some unarmoured vehicles.
- Move keeps the old format: `8"`, `10"j`, `12"t`, `24"h`; aerospace uses thrust (`10a`, `5p`).
- Chips: `DMG s/m/l/e` (`0*` = minimal damage), `OV n`, `TH n` (aerospace threshold), then one
  chip per special ability, then the role (`None` when there is none).
- Ability chips separate code and value with a space (`CAR 4`, `SRM 2/2`, `TUR (2/2/-,SRM2/2)`).
  Removing the whitespace gives the old `BFAbilities` token, so `BFAbilitiesParser` still works.
- The unit art is the `<img>` whose `alt` ends in `illustration`.

`UnitPageParser` reads this block; `mul:sync` caches the parsed result per unit as
`data/raw/units/{uid}.json` (≈1 KB each instead of ≈90 KB of HTML). With a copy of that
directory, a sync on another machine needs no unit-page requests at all.

## Unit art

Under `/point-backs/unit-art/…` on the MUL host (`.webp`, older units `.png`), so image downloads
fall under the same crawl delay.
